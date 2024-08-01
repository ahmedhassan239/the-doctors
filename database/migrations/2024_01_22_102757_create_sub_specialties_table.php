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
        Schema::create('sub_specialties', function (Blueprint $table) {
            $table->id();
            $table->longText('name');
            $table->longText('slug');
            $table->longText('description')->nullable();
            $table->foreignId('country_id')->constrained()->onDelete('cascade');
            $table->foreignId('specialtie_id')->constrained('specialties')->onDelete('cascade');
            $table->longText('overview')->nullable();
            $table->longText('seo_title')->nullable();
            $table->longText('seo_keywords')->nullable();
            $table->longText('seo_description')->nullable();
            $table->longText('robots')->nullable();
            $table->boolean('status')->default(1);
            $table->timestamps();
            $table->softDeletes(); // Soft delete column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_specialties');
    }
};
