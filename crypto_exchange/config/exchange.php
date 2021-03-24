<?php

return [
    // Конфигурация
    //
    // Включить/выключить проверку авторизации
    'auth_check' => env('AUTH_CHECK', true),
    // Токен авторизации
    'auth_bearer_token' => env('AUTH_BEARER_TOKEN', null),
    //
    // Размер комиссии
    'fee' => 0.02, // 2%
];
