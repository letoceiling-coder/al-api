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
        Schema::create('trendagent_complexes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained('trendagent_regions')->onDelete('cascade');
            $table->string('external_id', 255)->unique()->comment('ID из API (например: 63c50acc9a85d53360f63a76)');
            $table->string('guid', 255)->nullable()->comment('Slug (например: dom-na-naberezhnoy-st)');
            $table->string('name', 500);
            $table->text('address')->nullable();
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('developer_name', 255)->nullable();
            $table->string('class_type', 50)->nullable()->comment('Класс жилья');
            $table->string('deadline', 255)->nullable()->comment('Срок сдачи');
            $table->string('status', 50)->nullable()->comment('Статус комплекса');
            $table->integer('min_price')->nullable()->comment('Минимальная цена');
            $table->json('images')->nullable()->comment('Массив URL изображений');
            $table->json('advantages')->nullable()->comment('Преимущества комплекса');
            $table->json('nearby_places')->nullable()->comment('Ближайшие места');
            $table->json('videos')->nullable()->comment('Видео');
            $table->json('files')->nullable()->comment('Файлы (документы)');
            $table->json('raw_data')->nullable()->comment('Полные данные от unified API');
            $table->timestamps();
            
            $table->index('external_id');
            $table->index('guid');
            $table->index(['region_id', 'status']);
            $table->index('deadline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trendagent_complexes');
    }
};
