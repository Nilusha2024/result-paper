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

        // Schema for storing english feed event prizes (based on the EX files)
        // --------------------------------------------------------------------

        Schema::create('tbl_eng_result_event_prize', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('event_id')->nullable();
            $table->integer('position')->nullable();
            $table->double('amount')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_eng_result_event_prize');
    }
};
