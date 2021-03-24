<?php

namespace App\Services\Utils;

/**
 *
 * Утилиты
 *
 */
class HttpUtils {

    /**
     * Значения параметра по умолчанию для сеанса CURL.
     * Количество секунд ожидания при попытке соединения. Используйте 0 для бесконечного ожидания.
     *
     * @var string
     */
    const DEFAULT_CURLOPT_CONNECTTIMEOUT = 10;

    /**
     * Значения параметра по умолчанию для сеанса CURL.
     * Максимально позволенное количество секунд для выполнения cURL-функций.
     *
     * @var string
     */
    const DEFAULT_CURLOPT_TIMEOUT = 10;

    /**
     * По заданному http-коду возвращает признак успеха или неудачи запроса
     *
     * @param  string $httpCode  HTTP - код
     * @return bool Признак успеха или неудачи запроса
     */
    private static function httpCodeSuccess($httpCode) {
        return (200 <= $httpCode) && ($httpCode < 300);
    }

    /**
     * Возвращает массив параметров для сеанса CURL.
     *
     * @return array Массив с опциями Curl
     */
    private static function getCurlOptions() {
        return [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => self::DEFAULT_CURLOPT_CONNECTTIMEOUT,
            CURLOPT_TIMEOUT => self::DEFAULT_CURLOPT_TIMEOUT,
        ];
    }

    /**
     * Отправка запроса через curl.
     * Возвращает результат выполнения запроса в виде массива:
     * [
     *     'status' => {true|false},
     *     'response' => {string ответ сервера (curl_exec)},
     *     'info' => {дополнительная информация о запросе (curl_getinfo)},
     *     'code' => {http-код ответа},
     *     'error' => {Описание ошибки (при наличии таковой)}
     * ]
     *
     * @param  string $url  URL ресурса
     * @param  string $method  Метод
     * @param  array $postFields Данные, передаваемые в HTTP POST-запросе (если $method = 'POST')
     * @param  array $headers Массив заголовков в формате ['Content-type: text/plain', 'Content-length: 100']
     * @param  array $curlSetoptArray Массив дополнительных параметров для сеанса CURL (для curl_setopt_array)
     * @param  array $httpCodesSuccess Массив http-кодов, означающих успех
     * @return array Результат выполнения запроса
     */
    public static function curlRequest($url, $method = 'GET', $postFields = [], $headers = [], $curlSetoptArray = [], $httpCodesSuccess = []) {
        $result = [
            'status' => false,
            'error' => '',
        ];
        // Создаем дескриптор curl
        $curlHandler = curl_init($url);

        // Опции запроса
        // Метод
        curl_setopt($curlHandler, CURLOPT_CUSTOMREQUEST, $method);
        // Заголовки
        curl_setopt($curlHandler, CURLOPT_HTTPHEADER, $headers);
        // Дополнительные параметры сеанса CURL
        curl_setopt_array($curlHandler, self::getCurlOptions());
        curl_setopt_array($curlHandler, $curlSetoptArray);
        // Данные для POST - запроса
        if (!empty($postFields) && $method == 'POST') {
            curl_setopt($curlHandler, CURLOPT_SAFE_UPLOAD, true);
            curl_setopt($curlHandler, CURLOPT_POSTFIELDS, $postFields);
        }

        // Выполняем запрос
        $curlExec = curl_exec($curlHandler);
        $curlInfo = curl_getinfo($curlHandler);
        // http- код ответа
        $httpCode = $curlInfo['http_code'];

        $result['response'] = $curlExec;
        $result['info'] = $curlInfo;
        $result['code'] = $httpCode;

        if (($curlExec !== false) && ( (self::httpCodeSuccess($httpCode)) || (in_array($httpCode, $httpCodesSuccess)) )) {
            $result['status'] = true;
        } else {
            $result['error'] = curl_error($curlHandler);
        }
        // Закрываем дескриптор curl
        curl_close($curlHandler);
        return $result;
    }

    /**
     * По заданным базовому и относительному URL возвращает полный URL
     *
     * @param string $baseUrl Базовый URL - адрес
     * @param string $pathUrl Относительный URL - адрес
     * @return string Полный URL - адрес
     */
    public static function getFullUrl($baseUrl, $pathUrl) {
        return rtrim($baseUrl, ' /') . '/' . ltrim($pathUrl, ' /');
    }

    /**
     * Формирует ответ для статуса "ошибка"
     *
     * @param int $httpCode http - код ошибки, например: 403
     * @param string $message Сообщение об ошибке, например: Invalid token
     * @param array $headers Массив заголовков, добавляемых в ответ
     * @return array Ответ для статуса "ошибка"
     */
    public static function errorResponse($httpCode, $message, $headers = []) {
        $response = response()->json([
                    'status' => 'error',
                    'code' => $httpCode,
                    'message' => $message,
        ], $httpCode);
        if (!empty($headers)) {
            $response->withHeaders($headers);
        }
        
        return $response;
    }

}
