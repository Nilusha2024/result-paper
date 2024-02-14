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

        // Schema for storing english feed events (based on the ES files)
        // --------------------------------------------------------------

        Schema::create('tbl_eng_result_event', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('meeting_auto_id')->nullable();
            $table->string('meeting_code')->nullable();
            $table->string('name')->nullable();
            $table->string('event_type')->nullable();
            $table->boolean('is_virtual')->default(false);
            $table->bigInteger('event_id')->nullable();
            $table->integer('num')->nullable();
            $table->time('time')->nullable();
            $table->integer('places_expected')->nullable();
            $table->integer('each_way_places')->nullable();
            $table->string('coverage_code')->nullable();
            $table->string('course_type')->nullable();
            $table->string('surface')->nullable();
            $table->string('grade')->nullable();
            $table->string('handicap')->nullable();
            $table->string('status')->nullable();
            $table->integer('runners')->nullable();
            $table->string('going')->nullable();
            $table->string('distance')->nullable();
            $table->time('offtime')->nullable();
            $table->string('progress_code')->nullable();
            $table->string('pmsg')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_eng_result_event');
    }
};
