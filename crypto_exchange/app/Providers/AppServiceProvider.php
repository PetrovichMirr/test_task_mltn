<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider {

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        // Привязка интерфейса к реализации
        $this->app->bind(
                'App\Contracts\ExchangeRateInfo',
                'App\Services\RemoteProviders\BlockchainInfo'
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
        // Отключаем обёртку всего ответа ресурса в корневой ключ 'data'
        \App\Http\Resources\Rate::withoutWrapping();
    }

}
