<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\Utils\HttpUtils;

class CheckAuthToken {

    /**
     * Обрабатывает входящий запрос.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        // Проверять или нет статус авторизации
        $authCheck = config('exchange.auth_check');
        if (!$authCheck) {
            return $next($request);
        }

        // Токен в запросе
        $requestBearerToken = $request->bearerToken();
        if (!$requestBearerToken) {
            return HttpUtils::errorResponse(401, 'Token required', ['WWW-Authenticate' => 'Bearer']);
        }

        // Токен приложения
        $appBearerToken = config('exchange.auth_bearer_token');

        if ($requestBearerToken && $appBearerToken && ($requestBearerToken == $appBearerToken)) {
            return $next($request);
        }

        return HttpUtils::errorResponse(403, 'Invalid token');
    }

}
