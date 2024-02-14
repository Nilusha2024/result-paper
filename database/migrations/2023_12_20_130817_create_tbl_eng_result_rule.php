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
        // For storing rule4 tags which tracks ALLBETS
        Schema::create('tbl_eng_result_rule', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('event_id')->nullable();
            $table->bigInteger('competitor_id')->nullable();
            $table->bigInteger('rule_id')->nullable();
            $table->string('type')->nullable();
            $table->integer('deduction')->nullable();
            $table->integer('runner_deduction')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_eng_result_rule');
    }
};
