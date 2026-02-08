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
        Schema::create('trendagent_apartments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complex_id')->nullable()->constrained('trendagent_complexes')->onDelete('set null')->comment('Может быть NULL для отдельных квартир');
            $table->foreignId('building_id')->nullable()->constrained('trendagent_buildings')->onDelete('set null');
            $table->foreignId('section_id')->nullable()->constrained('trendagent_sections')->onDelete('set null');
            $table->foreignId('floor_id')->nullable()->constrained('trendagent_floors')->onDelete('set null');
            $table->string('external_id', 255)->unique();
            $table->string('number', 50)->nullable()->comment('Номер квартиры');
            $table->integer('rooms')->nullable()->comment('Количество комнат');
            $table->decimal('area_total', 10, 2)->nullable()->comment('Общая площадь');
            $table->decimal('area_living', 10, 2)->nullable()->comment('Жилая площадь');
            $table->decimal('area_kitchen', 10, 2)->nullable()->comment('Площадь кухни');
            $table->integer('floor')->nullable()->comment('Этаж');
            $table->integer('price_base')->nullable()->comment('Базовая цена');
            $table->integer('price_full')->nullable()->comment('Полная цена (100%)');
            $table->integer('price_per_sqm')->nullable()->comment('Цена за м²');
            $table->foreignId('finishing_type_id')->nullable()->constrained('trendagent_finishing_types')->onDelete('set null');
            $table->foreignId('status_id')->nullable()->constrained('trendagent_statuses')->onDelete('set null');
            $table->foreignId('balcony_type_id')->nullable()->constrained('trendagent_balcony_types')->onDelete('set null');
            $table->foreignId('view_type_id')->nullable()->constrained('trendagent_view_types')->onDelete('set null');
            $table->boolean('is_exclusive')->default(false);
            $table->boolean('is_booked')->default(false);
            $table->boolean('is_on_request')->default(false);
            $table->text('plan_image_url')->nullable();
            $table->json('images')->nullable();
            $table->json('raw_data')->nullable()->comment('Полные данные от unified API');
            $table->timestamps();
            
            $table->index('external_id');
            $table->index('complex_id');
            $table->index('price_base');
            $table->index('area_total');
            $table->index('floor');
            $table->index('rooms');
            $table->index('status_id');
            $table->index('finishing_type_id');
            $table->index(['complex_id', 'status_id', 'price_base'], 'idx_complex_status_price');
            $table->index(['rooms', 'area_total', 'price_base'], 'idx_rooms_area_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trendagent_apartments');
    }
};
