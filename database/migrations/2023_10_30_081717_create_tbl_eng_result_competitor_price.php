<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        // Schema for storing english feed competitor prices (based on the ES files)
        // -------------------------------------------------------------------------

        Schema::create('tbl_eng_result_competitor_price', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('competitor_id')->nullable();
            $table->bigInteger('price_id')->nullable();
            $table->time('time')->nullable();
            $table->string('fract')->nullable();
            $table->decimal('dec', 10, 4)->nullable();
            $table->integer('mktnum')->nullable();
            $table->string('mkttype')->nullable();
            $table->bigInteger('timestamp')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_eng_result_competitor_price');
    }
};
