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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->longText('healthcare_provider_id')->nullable();
            $table->longText('country_id')->nullable();
            $table->longText('governorate_id')->nullable();
            $table->longText('area_id')->nullable();
            $table->longText('name');
            $table->longText('slug');
            $table->longText('specialties')->nullable();
            $table->longText('doctors')->nullable();
            $table->longText('description')->nullable();
            $table->longText('overview')->nullable();
            $table->longText('location');
            $table->integer('waiting_time')->nullable();
            $table->integer('fees')->nullable();
            $table->longText('seo_title')->nullable();
            $table->longText('seo_keywords')->nullable();
            $table->longText('seo_description')->nullable();
            $table->longText('robots')->nullable();
            $table->boolean('status')->default(1);
            $table->boolean('featured')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
