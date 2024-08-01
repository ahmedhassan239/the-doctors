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
        Schema::create('healthcare_providers', function (Blueprint $table) {
            $table->id();
            $table->longText('name');
            $table->longText('slug');
            $table->enum('type', [1, 2, 3]); // Assuming default type is 1
            $table->foreignId('country_id')->constrained()->onDelete('cascade');
            $table->foreignId('governorate_id')->constrained()->onDelete('cascade');
            $table->foreignId('area_id')->constrained()->onDelete('cascade');
            $table->longText('description')->nullable();
            $table->longText('overview')->nullable();
            $table->integer('waiting_time')->nullable();
            $table->integer('fees')->nullable();
            $table->longText('seo_title')->nullable();
            $table->longText('seo_keywords')->nullable();
            $table->longText('seo_description')->nullable();
            $table->longText('robots')->nullable();
            $table->boolean('status')->default(1);
            $table->boolean('featured')->default(0);
            $table->integer('country_sort')->default(-1);
            $table->integer('governorate_sort')->default(-1);
            $table->integer('area_sort')->default(-1);
            $table->integer('specialty_sort')->default(-1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('healthcare_providers');
    }
};
