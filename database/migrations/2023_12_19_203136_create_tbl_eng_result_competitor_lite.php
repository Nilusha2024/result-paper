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

        // Since the EX files don't come with every single competitor sometimes,
        // Here's an alternate, more summerized edition ready for the paper of hte EngResultComptetitor

        Schema::create('tbl_eng_result_competitor_lite', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('event_id')->nullable();
            $table->string('name')->nullable();
            $table->bigInteger('competitor_id')->nullable();
            $table->string('jockey')->nullable();
            $table->string('jockey_allowance')->nullable();
            $table->integer('num')->nullable();
            $table->string('trainer')->nullable();
            $table->integer('finish_position')->nullable();
            $table->string('run_status')->nullable();
            $table->string('fav_status')->nullable();
            $table->string('deadheat')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_eng_result_competitor_lite');
    }
};
