<?php

namespace App\Http\Middleware;

use Closure;

class HandleApiResponse {

    /**
     * Обрабатывает входящий запрос.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        $response = $next($request);
        // Добавляем заголовок 'Access-Control-Allow-Origin: *' для разрешения кросс-доменных запросов
        return $response->header('Access-Control-Allow-Origin', '*');
    }

}
