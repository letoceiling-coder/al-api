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
        // Паркинги (комплексы паркингов)
        Schema::create('trendagent_parkings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complex_id')->nullable()->constrained('trendagent_complexes')->onDelete('set null');
            $table->string('external_id', 255)->unique();
            $table->string('name', 500)->nullable();
            $table->foreignId('parking_type_id')->nullable()->constrained('trendagent_parking_types')->onDelete('set null');
            $table->integer('total_places')->nullable();
            $table->integer('available_places')->nullable();
            $table->integer('price_base')->nullable()->comment('Базовая цена');
            $table->integer('price_per_month')->nullable()->comment('Цена за месяц');
            $table->json('images')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();
            
            $table->index('external_id');
            $table->index('complex_id');
            $table->index('price_base');
        });

        // Места парковки
        Schema::create('trendagent_parking_places', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_id')->constrained('trendagent_parkings')->onDelete('cascade');
            $table->string('external_id', 255);
            $table->string('number', 50)->nullable();
            $table->integer('level')->nullable()->comment('Уровень парковки');
            $table->foreignId('status_id')->nullable()->constrained('trendagent_statuses')->onDelete('set null');
            $table->integer('price')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();
            
            $table->index('parking_id');
            $table->index('external_id');
            $table->index('status_id');
            $table->unique(['parking_id', 'external_id'], 'unique_parking_place');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trendagent_parking_places');
        Schema::dropIfExists('trendagent_parkings');
    }
};
