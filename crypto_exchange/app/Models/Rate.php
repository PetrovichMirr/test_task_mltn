<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rate extends Model {

    /**
     * Таблица, связанная с моделью.
     *
     * @var string
     */
    protected $table = 'rates';

    /**
     * Атрибуты модели, для которых разрешено массовое назначение.
     *
     * @var array
     */
    protected $fillable = [
        'base_currency_id',
        'quote_currency_id',
        'buy',
        'sell',
    ];

    /**
     * Атрибуты модели, которые должны быть видимы при преобразовании в массив или в JSON.
     *
     * @var array
     */
    protected $visible = [
        'baseCurrency',
        'quoteCurrency',
        'buy',
        'sell',
    ];

    /**
     * Отношение между моделями. Базовая валюта
     */
    public function baseCurrency() {
        return $this->belongsTo('App\Models\Currency', 'base_currency_id', 'id');
    }

    /**
     * Отношение между моделями. Котируемая валюта
     */
    public function quoteCurrency() {
        return $this->belongsTo('App\Models\Currency', 'quote_currency_id', 'id');
    }

}
