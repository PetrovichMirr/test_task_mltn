<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Services\ExchangeData;
use App\Services\Utils\HttpUtils;
use App\Models\Rate;
use App\Http\Resources\Rate as RateResource;
use App\Http\Resources\ConvertRequest as ConvertRequestResource;

/**
 * Контроллер API
 *
 * @author petrovichmirr
 */
class ApiV1Controller extends Controller {

    /**
     * Текст сообщения при ошибке получения данных курсов
     *
     * @var string
     */
    const RESPONSE_ERROR_MESSAGE_GET_RATES_FAILED = 'Ошибка получения данных валютных котировок. Повторите запрос позднее.';

    /**
     * Текст сообщения при ошибке "Не все обязательные параметры запроса получены."
     *
     * @var string
     */
    const RESPONSE_ERROR_MESSAGE_CONVERT_NO_HAS_REQUIRED_KEYS = 'Ошибка. Не все обязательные параметры запроса получены.';

    /**
     * Наименования (ключи) обязательных полей для метода convert
     *
     * @var array
     */
    const CONVERT_REQUIRED_KEYS = [
        'currency_from',
        'currency_to',
        'value',
    ];

    /**
     * Обработчик данных курсов валют
     *
     * @var \App\Services\ExchangeData
     */
    private $exchangeData;

    /**
     * Создание экземпляра класса
     *
     * @return this
     */
    public function __construct(ExchangeData $exchangeData) {
        // Получаем экземпляр ExchangeData через Dependency Injection
        // Реализация интерфейса App\Contracts\ExchangeRateInfo для
        // создания экземпляра ExchangeData зарегистрирована
        // в App\Providers\AppServiceProvider
        $this->exchangeData = $exchangeData;
    }

    /**
     * Обновляет данные по курсам валют
     *
     * @return bool Если true - обновление данных прошло успешно, иначе - false
     */
    private function updateRates() {
        return $this->exchangeData->updateRates();
    }

    /**
     * Формирует ответ для статуса "ошибка"
     *
     * @param int $httpCode http - код ошибки, например: 403
     * @param string $message Сообщение об ошибке, например: Invalid token
     * @return array Ответ для статуса "ошибка"
     */
    private function errorResponse($httpCode, $message) {
        return HttpUtils::errorResponse($httpCode, $message);
    }

    /**
     * Метод API. Версия 1
     * Получение всех курсов с учетом комиссии.
     * Внимание! По ТЗ у нас базовая валюта для всех случаев - BTC
     * URL: GET rates
     * Внимание! В ТЗ требуемый формат ответа указан такой:
     * Формат ответа по ТЗ:
     * {
     *     Код_котируемой_валюты: курс,
     *     ...
     * }
     * Здесь не совсем понятно, что за курс - на покупку или на продажу.
     * Опять же, согласно ТЗ, курс в ответе должен быть указан с учётом комиссии,
     * из этого следует, что курсы на покупку и на продажу будут разными в любом случае
     * Исходя из этого, мы немного изменим формат ответа, он будет такой:
     * Формат ответа, принятый в проекте:
     * {
     *     Код_котируемой_валюты: {"buy":"курс покупки","sell":"курс продажи"},
     *     ...
     * }
     *
     * Сортировка от меньшего курса к большему курсу.
     * В качестве параметров может передаваться интересующая валюта:
     * rates?filter[currency]=USD
     * В этом случае, отдаем указанные в качестве параметра currency значения.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function getRates() {
        // Обновляем информацию по курсам
        if (!$this->updateRates()) {
            // Если обновление не удалось
            return $this->errorResponse(500, self::RESPONSE_ERROR_MESSAGE_GET_RATES_FAILED);
        }

        try {
            // Оборачиваем в блок try .. catch, потому как QueryBuilder
            // выбрасывает исключения по поводу и без
            // повода (например, неверное имя фильтра в URL)
            $rates = QueryBuilder::for(Rate::class)
                    ->with('quoteCurrency')
                    ->allowedFilters(AllowedFilter::exact('currency', 'quoteCurrency.code'))
                    ->orderBy('buy', 'asc')
                    ->get();
        } catch (\Exception $e) {
            return $this->errorResponse(400, $e->getMessage());
        }

        // Для вывода в виде объекта, а не массива,
        // необходимо заменить числовые ключи коллекции (по умолчанию) на
        // строковые (в нашем случае - это код валюты)
        return RateResource::collection($rates->keyBy(function ($item, $key) {
                            return $item->quoteCurrency->code;
                        }));
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
    private function getConvertRequestData($currencyFromCode, $currencyToCode, $amount) {
        return $this->exchangeData->getConvertRequestData($currencyFromCode, $currencyToCode, $amount);
    }

    /**
     * Метод API. Версия 1
     * Запрос на конвертацию валют, результат запроса сохранять в базу.
     * URL: POST convert
     * Параметры запроса:
     * currency_from: USD - исходная валюта
     * currency_to: BTC - валюта в которую конвертируем
     * value: 1.00 - количество единиц исходной валюты
     *
     * Формат ответа:
     * {
     *     "currency_from": код исходной валюты,
     *     "currency_to": код валюты, в которую конвертируем,
     *     "value": объём исходной валюты,
     *     "converted_value": объём валюты, в которую конвертируем,
     *     "rate": курс,
     *     "created_at": TIMESTAMP
     * }
     *
     * @param \Illuminate\Http\Request $request Http-запрос
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function convert(Request $request) {
        // Обновляем информацию по курсам
        if (!$this->updateRates()) {
            // Если обновление не удалось
            return $this->errorResponse(500, self::RESPONSE_ERROR_MESSAGE_GET_RATES_FAILED);
        }
        if (!$request->has(self::CONVERT_REQUIRED_KEYS)) {
            // Не все данные обязательных полей получены
            return $this->errorResponse(400, self::RESPONSE_ERROR_MESSAGE_CONVERT_NO_HAS_REQUIRED_KEYS);
        }

        $currencyFromCode = $request->input('currency_from');
        $currencyToCode = $request->input('currency_to');
        $amount = $request->input('value');

        $convertRequestData = $this->getConvertRequestData($currencyFromCode, $currencyToCode, $amount);

        if (!$convertRequestData['status']) {
            return $this->errorResponse(400, $convertRequestData['message']);
        }

        return new ConvertRequestResource($convertRequestData['data']);
    }

}
