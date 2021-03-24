<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model {

    /**
     * Таблица, связанная с моделью.
     *
     * @var string
     */
    protected $table = 'currencies';

    /**
     * Атрибуты модели, для которых разрешено массовое назначение.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'precision',
        'min_amount_from',
    ];

    /**
     * Атрибуты модели, которые должны быть видимы при преобразовании в массив или в JSON.
     *
     * @var array
     */
    protected $visible = [
        'code',
        'precision',
        'min_amount_from',
    ];

    /**
     * Отношение между моделями. Курсы базовой валюты
     */
    public function baseCurrencyRates() {
        return $this->hasMany('App\Models\Rate', 'base_currency_id', 'id');
    }

    /**
     * Отношение между моделями. Курсы котируемой валюты
     */
    public function quoteCurrencyRates() {
        return $this->hasMany('App\Models\Rate', 'quote_currency_id', 'id');
    }

    /**
     * Отношение между моделями. Валюты для обмена (отправляемые)
     */
    public function currencyFromConvertRequests() {
        return $this->hasMany('App\Models\ConvertRequest', 'currency_from_id', 'id');
    }

    /**
     * Отношение между моделями. Валюты для обмена (отправляемые)
     */
    public function currencyToConvertRequests() {
        return $this->hasMany('App\Models\ConvertRequest', 'currency_to_id', 'id');
    }

}
