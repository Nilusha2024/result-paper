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

        // Schema for storing english feed meetings (based on the MI files)
        // ----------------------------------------------------------------

        Schema::create('tbl_eng_result_meeting', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->string('country')->nullable();
            $table->string('category')->nullable();
            $table->string('sportcode')->nullable();
            $table->datetime('date')->nullable();
            $table->integer('events')->nullable();
            $table->string('status')->nullable();
            $table->string('coverage_code')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('going')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_eng_result_meeting');
    }
};
