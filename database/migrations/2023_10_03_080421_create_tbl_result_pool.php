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
        Schema::create('tbl_result_pool', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->nullable();
            $table->string('pool_id')->nullable();
            $table->string('pool_type')->nullable();
            $table->double('jackpot')->nullable();
            $table->integer('leg_number')->nullable();
            $table->string('status')->nullable();
            $table->double('pool_total')->nullable();
            $table->string('substitute')->nullable();
            $table->datetime('closed_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_result_pool');
    }
};
