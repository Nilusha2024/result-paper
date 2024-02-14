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
        Schema::create('tbl_result_competitor', function (Blueprint $table) {
            $table->id();
            $table->integer('competitor_id')->nullable();
            $table->string('event_id')->nullable();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->integer('competitor_no')->nullable();
            $table->string('competitor_type')->nullable();
            $table->integer('post_no')->nullable();
            $table->integer('finish_position')->nullable();
            $table->string('form')->nullable();
            $table->double('weight')->nullable();
            $table->string('jockey')->nullable();
            $table->string('trainer')->nullable();
            $table->string('status')->nullable();
            $table->string('fav_status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_result_competitor');
    }
};
