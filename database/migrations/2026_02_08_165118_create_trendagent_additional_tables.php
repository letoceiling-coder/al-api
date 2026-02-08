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
        // Поэтажные планы
        Schema::create('trendagent_floor_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complex_id')->constrained('trendagent_complexes')->onDelete('cascade');
            $table->foreignId('building_id')->nullable()->constrained('trendagent_buildings')->onDelete('set null');
            $table->foreignId('section_id')->nullable()->constrained('trendagent_sections')->onDelete('set null');
            $table->foreignId('floor_id')->nullable()->constrained('trendagent_floors')->onDelete('set null');
            $table->integer('floor_number');
            $table->text('image_url')->nullable();
            $table->json('interactive_data')->nullable()->comment('Интерактивные данные плана');
            $table->json('raw_data')->nullable();
            $table->timestamps();
            
            $table->index('complex_id');
            $table->index(['building_id', 'section_id', 'floor_number'], 'idx_building_section_floor');
            $table->unique(['complex_id', 'building_id', 'section_id', 'floor_number'], 'unique_plan');
        });

        // Изображения (опционально, можно хранить только URL в JSON)
        Schema::create('trendagent_images', function (Blueprint $table) {
            $table->id();
            $table->string('object_type', 50)->comment('apartment, complex, house, plot, commercial');
            $table->unsignedBigInteger('object_id');
            $table->text('url');
            $table->string('type', 50)->nullable()->comment('gallery, plan, view');
            $table->integer('order_index')->default(0);
            $table->string('local_path', 500)->nullable()->comment('Локальный путь, если изображение скачано');
            $table->timestamps();
            
            $table->index(['object_type', 'object_id'], 'idx_object');
            $table->index('type');
            $table->index('order_index');
        });

        // Ближайшие места
        Schema::create('trendagent_nearby_places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complex_id')->constrained('trendagent_complexes')->onDelete('cascade');
            $table->string('name', 255);
            $table->string('type', 50)->nullable()->comment('subway, school, shop, и т.д.');
            $table->integer('distance')->nullable()->comment('Расстояние в метрах');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();
            
            $table->index('complex_id');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trendagent_nearby_places');
        Schema::dropIfExists('trendagent_images');
        Schema::dropIfExists('trendagent_floor_plans');
    }
};
