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
        Schema::create('tbl_result_competitor_price', function (Blueprint $table) {
            $table->id();
            $table->integer('competitor_id')->nullable();
            $table->double('odds')->nullable();
            $table->string('price_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_result_competitor_price');
    }
};
