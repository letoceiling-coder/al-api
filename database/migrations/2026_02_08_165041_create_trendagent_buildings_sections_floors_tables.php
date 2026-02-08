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
        // Корпуса в комплексах
        Schema::create('trendagent_buildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complex_id')->constrained('trendagent_complexes')->onDelete('cascade');
            $table->string('external_id', 255);
            $table->string('name', 255)->nullable();
            $table->string('number', 50)->nullable();
            $table->integer('sections_count')->nullable();
            $table->integer('floors_count')->nullable();
            $table->integer('apartments_count')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();
            
            $table->index('complex_id');
            $table->index('external_id');
            $table->unique(['complex_id', 'external_id'], 'unique_complex_building');
        });

        // Секции в корпусах
        Schema::create('trendagent_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('trendagent_buildings')->onDelete('cascade');
            $table->string('external_id', 255);
            $table->string('name', 255)->nullable();
            $table->string('number', 50)->nullable();
            $table->integer('floors_count')->nullable();
            $table->integer('apartments_count')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();
            
            $table->index('building_id');
            $table->index('external_id');
            $table->unique(['building_id', 'external_id'], 'unique_building_section');
        });

        // Этажи в секциях
        Schema::create('trendagent_floors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('trendagent_sections')->onDelete('cascade');
            $table->string('external_id', 255)->nullable();
            $table->integer('number');
            $table->integer('apartments_count')->nullable();
            $table->text('plan_image_url')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();
            
            $table->index('section_id');
            $table->index('number');
            $table->unique(['section_id', 'number'], 'unique_section_floor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trendagent_floors');
        Schema::dropIfExists('trendagent_sections');
        Schema::dropIfExists('trendagent_buildings');
    }
};
