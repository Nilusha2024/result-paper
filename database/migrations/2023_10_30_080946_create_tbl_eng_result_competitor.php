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

        // Schema for storing english feed competitors (based on the ES files)
        // -------------------------------------------------------------------

        Schema::create('tbl_eng_result_competitor', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('event_id')->nullable();
            $table->string('name')->nullable();
            $table->bigInteger('competitor_id')->nullable();
            $table->string('short_name')->nullable();
            $table->string('jockey')->nullable();
            $table->string('jockey_allowance')->nullable();
            $table->string('short_jockey')->nullable();
            $table->integer('num')->nullable();
            $table->integer('age')->nullable();
            $table->string('trainer')->nullable();
            $table->string('owner')->nullable();
            $table->string('dam')->nullable();
            $table->string('sire')->nullable();
            $table->string('damsire')->nullable();
            $table->string('bred')->nullable();
            $table->double('weight')->nullable();
            $table->datetime('born_date')->nullable();
            $table->string('color')->nullable();
            $table->string('sex')->nullable();
            $table->integer('finish_position')->nullable();
            $table->string('fav_status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_eng_result_competitor');
    }
};
