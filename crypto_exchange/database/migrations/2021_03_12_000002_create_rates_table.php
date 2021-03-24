<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRatesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('rates', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('base_currency_id');
            $table->foreign('base_currency_id')->references('id')->on('currencies');

            $table->unsignedBigInteger('quote_currency_id');
            $table->foreign('quote_currency_id')->references('id')->on('currencies');

            $table->unsignedDecimal('buy', 24, 12);
            $table->unsignedDecimal('sell', 24, 12);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('rates');
    }

}
