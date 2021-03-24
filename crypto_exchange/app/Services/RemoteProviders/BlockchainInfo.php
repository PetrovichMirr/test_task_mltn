<?php

namespace App\Services\RemoteProviders;

use Illuminate\Support\Facades\Log;
use App\Services\Utils\HttpUtils;
use App\Contracts\ExchangeRateInfo;

/**
 * Взаимодействие с удалённым поставщиком услуг.
 * Поставщик услуг: https://blockchain.info
 *
 * @author petrovichmirr
 */
class BlockchainInfo implements ExchangeRateInfo {

    /**
     * Текст сообщения при ошибке запроса к API поставщика услуг
     *
     * @var string
     */
    const SEND_REQUEST_ERROR_MESSAGE = 'Ошибка запроса к API поставщика услуг. ';

    /**
     * Конечная точка (базовый URL) API поставщика услуг
     *
     * @var string
     */
    private $apiEndPoint = 'https://blockchain.info'; // Указываем этот адрес согласно ТЗ, хотя end-point основного API другой (https://api.blockchain.com/v3/exchange)

    /**
     * Создание экземпляра класса
     *
     * @param string $apiEndPoint Конечная точка (базовый URL) API поставщика услуг
     * @return this
     */
    public function __construct($apiEndPoint = null) {
        if (!empty($apiEndPoint)) {
            $this->apiEndPoint = $apiEndPoint;
        }
    }

    /**
     * Возвращает полный URL - адрес метода API поставщика услуг
     *
     * @param string $apiMethodUrl Относительный адрес метода API поставщика услуг (как правило, имя метода API)
     * @return string Полный URL - адрес метода API поставщика услуг
     */
    private function getFullApiUrl($apiMethodUrl) {
        return HttpUtils::getFullUrl($this->apiEndPoint, $apiMethodUrl);
    }

    /**
     * Выполняет запрос к методу API поставщика услуг
     *
     * @param string $apiMethodUrl Относительный адрес метода API поставщика услуг (как правило, имя метода API)
     * string $method Метод запроса
     * @param mixed $sendData Параметры запроса
     * @return mixed Ответ метода API поставщика услуг
     */
    private function sendRequest($apiMethodUrl, $method = 'GET', $sendData = null) {
        $result = [
            'status' => false,
            'response' => null,
            'error' => null,
        ];

        $fullUrl = $this->getFullApiUrl($apiMethodUrl);
        $postFields = empty($sendData) ? null : json_encode($sendData);
        $headers = ['Content-type: application/json'];

        $curlRequestResponse = HttpUtils::curlRequest($fullUrl, $method, $postFields, $headers);
        if (!$curlRequestResponse['status']) {
            $errorMessage = self::SEND_REQUEST_ERROR_MESSAGE . $curlRequestResponse['error'];
            Log::error($errorMessage . ' Подробные данные запроса: ' . print_r($curlRequestResponse, true));
            $result['error'] = $errorMessage;
            return $result;
        }

        $result['status'] = true;
        $result['response'] = json_decode($curlRequestResponse['response'], true);
        return $result;
    }

    /**
     * Выполняет запрос к заданному методу API поставщика услуг и возвращает ответ.
     * Метод API: ticker
     *
     * @return this
     */
    public function apiRequestTicker() {
        return $this->sendRequest('ticker');
    }

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
    public function getRates() {
        $apiRequestTickerResponse = $this->apiRequestTicker();
        // Внимание! По ТЗ у нас базовая валюта для всех случаев - BTC
        return $apiRequestTickerResponse['status'] ? ['BTC' => $apiRequestTickerResponse['response']] : null;
    }

}
