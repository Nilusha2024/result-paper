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
        // Schema for storing english feed racebets (based on the ES files)
        // ----------------------------------------------------------------

        Schema::create('tbl_eng_result_racebet', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('event_id')->nullable();
            $table->bigInteger('racebet_id')->nullable();
            $table->string('bet_type')->nullable();
            $table->double('amount')->nullable();
            $table->integer('instance')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_eng_result_racebet');
    }
};
