<?php

namespace App\Contracts;

/**
 * Информация о курсах валют
 *
 * @author petrovichmirr
 */
interface ExchangeRateInfo {

    /**
     * Возвращает данные по курсам валют в виде ассоциативного массива в формате:
     * [
     *    base_currency_code => [
     *          quote_currency_code => ['buy' => buy_price, 'sell' => sell_price],
     *          ...
     *       ]
     * ]
     * В случае ошибки или отсутствия данных возвращает null
     *
     * @return array|null Данные по курсам валют
     */
    public function getRates();
}
