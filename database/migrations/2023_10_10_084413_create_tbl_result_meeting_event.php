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

        // NOTE : Putting this here in cause of any confusion.
        // This schema is used to store the events that are directly under a meeting tag
        // Because event data under competitions had different attributes compared to this one
        // Wanted to store both just in case
        // Found in single meeting index files
        // Ex: ABKmeetingindex.xml

        Schema::create('tbl_result_meeting_event', function (Blueprint $table) {
            $table->id();
            $table->integer('meeting_id')->nullable();
            $table->integer('runners')->nullable();
            $table->integer('distance')->nullable();
            $table->string('name')->nullable();
            $table->integer('number')->nullable();
            $table->datetime('start_time')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_result_meeting_event');
    }
};
