<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConvertRequestsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('convert_requests', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('currency_from_id');
            $table->foreign('currency_from_id')->references('id')->on('currencies');

            $table->unsignedBigInteger('currency_to_id');
            $table->foreign('currency_to_id')->references('id')->on('currencies');

            $table->unsignedDecimal('price', 24, 12);
            $table->unsignedDecimal('amount', 24, 12);
            $table->unsignedDecimal('converted_amount', 24, 12);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('convert_requests');
    }

}
