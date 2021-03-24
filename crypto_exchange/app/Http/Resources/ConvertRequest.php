<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConvertRequest extends JsonResource {

    /**
     * Преобразует ресурс в массив.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request) {
        return [
            'currency_from' => $this->currencyFrom->code,
            'currency_to' => $this->currencyTo->code,
            'value' => $this->amount,
            'converted_value' => $this->converted_amount,
            'rate' => $this->price,
            'created_at' => $this->created_at,
        ];
    }

}
