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

        Schema::create('tbl_result_dividend', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->nullable();
            $table->string('event_type')->nullable();
            $table->string('dividend_type')->nullable();
            $table->integer('instance')->nullable();
            $table->double('dividend_amount')->nullable();
            $table->double('jackpot_carried_over')->nullable();
            $table->string('status')->nullable();
            $table->string('runner_numbers')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_result_dividend');
    }
};
