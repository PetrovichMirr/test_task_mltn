<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\Rate;
use App\Models\Currency;
use App\Models\ConvertRequest;
use App\Contracts\ExchangeRateInfo;

/**
 * Обрабатывает данные курсов валют
 *
 * @author petrovichmirr
 */
class ExchangeData {

    /**
     * Данные валют - точность (количество знаков после запятой),
     * минимальный объём для обмена
     * ПРИМЕЧАНИЕ:
     * В ТЗ указано, что точность для валюты BTC - 10 знаков,
     * однако, на самом деле - 8 знаков. В данном проекте
     * применяем точность в 8 знаков.
     * Для всех остальных валют применена точность в 2 знака.
     *
     * Также в ТЗ указано, что минимальный объём для обмена для всех валют 0.01
     * В данном проекте минимальный объём обмена для BTC 0,0000002,
     * для всех остальных валют 0.01
     *
     * @var string
     */
    const CURRENCIES_DATA = [
        'BTC' => [
            'precision' => 8,
            'min_amount_from' => 0.0000002,
        ],
        'DEFAULT' => [
            'precision' => 2,
            'min_amount_from' => 0.01,
        ],
    ];

    /**
     * Сторона сделки. Покупка
     *
     * @var string
     */
    const TRADE_SIDE_BUY = 'buy';

    /**
     * Сторона сделки. Покупка
     *
     * @var string
     */
    const TRADE_SIDE_SELL = 'sell';

    /**
     * Текст ошибки. Неизвестное значение стороны сделки
     *
     * @var string
     */
    const ERROR_UNKNOWN_TRADE_SIDE = 'Ошибка. Неизвестное значение стороны сделки';

    /**
     * Текст сообщения при ошибке получения данных курсов
     *
     * @var string
     */
    const ERROR_MESSAGE_PAIR_FOR_EXCHANGE_NOT_FOUND = 'Ошибка. Не найдена валютная пара для указанного направления обмена.';

    /**
     * Текст сообщения при ошибке получения данных курсов
     *
     * @var string
     */
    const ERROR_MESSAGE_AMOUNT_FOR_EXCHANGE_TO_SMALL = 'Ошибка. Указанный объём валюты для обмена меньше минимально допустимого.';

    /**
     * Поставщик данных по курсам валют
     *
     * @var \App\Contracts\ExchangeRateInfo
     */
    private $exchangeRateInfo;

    /**
     * Создание экземпляра класса
     *
     * @param \App\Contracts\ExchangeRateInfo $exchangeRateInfo Поставщик данных по курсам валют
     * @return this
     */
    public function __construct(ExchangeRateInfo $exchangeRateInfo) {
        $this->exchangeRateInfo = $exchangeRateInfo;
    }

    /**
     * Возвращает размер комиссии
     *
     * @return void
     */
    private function getFee() {
        return config('exchange.fee');
    }

    /**
     * Возвращает данные для заданной валюты -
     * точность (количество знаков после запятой), минимальный объём для обмена
     *
     * @param string $currencyCode Код валюты
     * @return array Возвращает данные для заданной валюты
     */
    private function getCurrencyData($currencyCode) {
        return isset(self::CURRENCIES_DATA[$currencyCode]) ? self::CURRENCIES_DATA[$currencyCode] : self::CURRENCIES_DATA['DEFAULT'];
    }

    /**
     * Обновляет данные по курсам валют
     *
     * @return bool Если true - обновление данных прошло успешно, иначе - false
     */
    public function updateRates() {
        $ratesData = $this->exchangeRateInfo->getRates();
        if (empty($ratesData)) {
            return false;
        }

        // Пока такой алгоритм обновления данных в БД -
        // по-хорошему, требуется рефакторинг - слишком много запросов к БД и т.д.
        // К примеру, можно использовать такой подход:
        // DB::table('таблица')->insert([массив нескольких записей]);
        // либо другой, на данном этапе пока делаем так
        // Сейчас просто полностью очищаем таблицу
        Rate::truncate();
        foreach ($ratesData as $baseCurrencyCode => $currencyRates) {
            foreach ($currencyRates as $quoteCurrencyCode => $rates) {

                $baseCurrencyData = $this->getCurrencyData($baseCurrencyCode);
                $baseCurrency = Currency::firstOrCreate(
                                ['code' => $baseCurrencyCode],
                                ['precision' => $baseCurrencyData['precision'],
                                    'min_amount_from' => $baseCurrencyData['min_amount_from']]);

                $quoteCurrencyData = $this->getCurrencyData($quoteCurrencyCode);
                $quoteCurrency = Currency::firstOrCreate(
                                ['code' => $quoteCurrencyCode],
                                ['precision' => $quoteCurrencyData['precision'],
                                    'min_amount_from' => $quoteCurrencyData['min_amount_from']]);

                $rate = new Rate([
                    'base_currency_id' => $baseCurrency->id,
                    'quote_currency_id' => $quoteCurrency->id,
                    'buy' => round($rates['buy'] / (1 - $this->getFee()), $quoteCurrency->precision), // с учётом комиссии
                    'sell' => round($rates['sell'] * (1 - $this->getFee()), $quoteCurrency->precision), // с учётом комиссии
                ]);
                $rate->save();
            }
        }
        return true;
    }

    /**
     * Возвращает курс по заданной валютной паре. Если не найден, возвращает null
     * ['status' => true|false, 'data' => ConvertRequest|null, 'message' => сообщение (при ошибке)]
     *
     * ВНИМАНИЕ!!! Метод не ищет кросс-курсы, только прямые направления обмена!
     * Например, у нас есть в базе данных два курса:
     * BTC/EUR и BTC/USD, но нет USD/EUR (кросс-курс)
     * Метод может найти данные обмена по следующим направлениям:
     * BTC -> EUR, EUR -> BTC, BTC -> USD, USD -> BTC,
     * но не может по таким: EUR -> USD, USD -> EUR
     *
     * @param string $currencyFromCode Код исходной валюты
     * @param string $currencyToCode Код валюты, в которую конвертируем
     * @return \App\Models\Rate|null Курс по заданной валютной паре. Если не найден, возвращает null
     */
    private function findRate($currencyFromCode, $currencyToCode) {

        // Вариант кода 1:
        $rate = Rate::whereHas('baseCurrency', function (Builder $query) use ($currencyFromCode) {
                    $query->where('code', '=', $currencyFromCode);
                })->whereHas('quoteCurrency', function (Builder $query) use ($currencyToCode) {
                    $query->where('code', '=', $currencyToCode);
                })->first();

        // Вариант кода 2:
//        $currencyFrom = Currency::where('code', '=', $currencyFromCode)->first();
//        $rate = $currencyFrom->baseCurrencyRates->where('quoteCurrency.code', '=', $currencyToCode)->first();
        // Вариант кода 3:
//        $currencyFrom = Currency::where('code', '=', $currencyFromCode)->first();
//        $currencyTo = Currency::where('code', '=', $currencyToCode)->first();
//        $rate = Rate::where('base_currency_id', '=', $currencyFrom->id)
//                ->where('quote_currency_id', '=', $currencyTo->id)->first();

        return $rate;
    }

    /**
     * Возвращает данные обмена по заданной валютной паре в формате:
     * ['status' => true|false, 'data' => ConvertRequest|null, 'message' => сообщение (при ошибке)]
     *
     * ВНИМАНИЕ!!! Метод не считает кросс-курсы, только прямые направления обмена!
     * Например, у нас есть в базе данных два курса:
     * BTC/EUR и BTC/USD, но нет USD/EUR (кросс-курс)
     * Метод может посчитать данные обмена по следующим направлениям:
     * BTC -> EUR, EUR -> BTC, BTC -> USD, USD -> BTC,
     * но не может по таким: EUR -> USD, USD -> EUR
     *
     * @param string $currencyFromCode Код исходной валюты
     * @param string $currencyToCode Код валюты, в которую конвертируем
     * @param string $amount Объём исходной валюты
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function getConvertRequestData($currencyFromCode, $currencyToCode, $amount) {
        $result = [
            'status' => false,
            'data' => null,
            'message' => null,
        ];

        $currencyFrom = Currency::where('code', '=', $currencyFromCode)->first();
        $currencyTo = Currency::where('code', '=', $currencyToCode)->first();

        function findRate($currencyFromCodeId, $currencyToCodeId) {
            return Rate::where('base_currency_id', '=', $currencyFromCodeId)
                            ->where('quote_currency_id', '=', $currencyToCodeId)->first();
        }

        // Ищем прямой курс (прямая котировка)
        //
        // С различными вариантами кода по нахождению моделей через зависимости - $this->findRate
        //$rate = $this->findRate($currencyFromCode, $currencyToCode);
        $rate = findRate($currencyFrom->id, $currencyTo->id);

        // сторона обмена
        // на прямом курсе - продажа
        // (например, BTC -> USD на паре BTC/USD - это продажа BTC)
        $side = self::TRADE_SIDE_SELL;
        if (!$rate) {
            // Если нет прямого, ищем обратный курс (обратная котировка)
            //
            // С различными вариантами кода по нахождению моделей через зависимости - $this->findRate
            //$rate = $this->findRate($currencyToCode, $currencyFromCode);
            $rate = findRate($currencyTo->id, $currencyFrom->id);
            // сторона обмена
            // на обратном курсе - покупка
            // (например, USD -> BTC на паре BTC/USD - это покупка BTC)
            $side = self::TRADE_SIDE_BUY;
        }

        // Подходящая пара не найдена
        if (!$rate) {
            $result['message'] = self::ERROR_MESSAGE_PAIR_FOR_EXCHANGE_NOT_FOUND;
            return $result;
        }

        // Проверка на минимальную сумму обмена
        if ($amount < $currencyFrom->min_amount_from) {
            $result['message'] = self::ERROR_MESSAGE_AMOUNT_FOR_EXCHANGE_TO_SMALL;
            return $result;
        }

        // Цена сделки, объём получаемой валюты
        $price = $rate->$side;
        $convertedAmount = round(($side == self::TRADE_SIDE_SELL ? $amount * $price : $amount / $price), $currencyTo->precision);

        // Сохраняем запрос в БД и возвращаем данные
        $convertRequest = new ConvertRequest([
            'currency_from_id' => $currencyFrom->id,
            'currency_to_id' => $currencyTo->id,
            'price' => $price,
            'amount' => $amount,
            'converted_amount' => $convertedAmount,
        ]);
        $convertRequest->save();

        $result['status'] = true;
        $result['data'] = $convertRequest;
        return $result;
    }

}
