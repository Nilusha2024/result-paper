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
        Schema::create('tbl_result_event', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->nullable();
            $table->string('meeting_id')->nullable();
            $table->string('event_name')->nullable();
            $table->string('event_type')->nullable();
            $table->integer('race_num')->nullable();
            $table->text('description')->nullable();
            $table->datetime('start_datetime')->nullable();
            $table->datetime('utc_start_datetime')->nullable();
            $table->datetime('end_datetime')->nullable();
            $table->string('going')->nullable();
            $table->string('status')->nullable();
            $table->integer('length')->nullable();
            $table->string('country_name')->nullable();
            $table->string('country_code')->nullable();
            $table->string('location_code')->nullable();
            $table->integer('mtp')->nullable();
            $table->datetime('closed_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_result_event');
    }
};
