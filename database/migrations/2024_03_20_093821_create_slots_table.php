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
        Schema::create('slots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_day_id');
            $table->bigInteger('start_from');
            $table->bigInteger('end_to');
            $table->timestamps();

            // Assuming you have a 'schedule_days' table with an 'id' column
            $table->foreign('schedule_day_id')->references('id')->on('schedule_days')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slots');
    }
};
