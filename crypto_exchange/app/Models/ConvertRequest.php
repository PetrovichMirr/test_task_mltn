<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConvertRequest extends Model {

    /**
     * Таблица, связанная с моделью.
     *
     * @var string
     */
    protected $table = 'convert_requests';

    /**
     * Атрибуты модели, для которых разрешено массовое назначение.
     *
     * @var array
     */
    protected $fillable = [
        'currency_from_id',
        'currency_to_id',
        'price',
        'amount',
        'converted_amount',
    ];

    /**
     * Атрибуты модели, которые должны быть видимы при преобразовании в массив или в JSON.
     *
     * @var array
     */
    protected $visible = [
        'price',
        'amount',
        'converted_amount',
    ];

    /**
     * Отношение между моделями. Валюта для обмена (отправляемая)
     */
    public function currencyFrom() {
        return $this->belongsTo('App\Models\Currency', 'currency_from_id', 'id');
    }

    /**
     * Отношение между моделями. Валюта для обмена (получаемая)
     */
    public function currencyTo() {
        return $this->belongsTo('App\Models\Currency', 'currency_to_id', 'id');
    }

}
