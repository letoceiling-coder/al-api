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
        Schema::create('trendagent_commercial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complex_id')->nullable()->constrained('trendagent_complexes')->onDelete('set null');
            $table->string('external_id', 255)->unique();
            $table->string('name', 500)->nullable();
            $table->foreignId('commercial_type_id')->nullable()->constrained('trendagent_commercial_types')->onDelete('set null');
            $table->foreignId('business_type_id')->nullable()->constrained('trendagent_business_types')->onDelete('set null');
            $table->decimal('area_total', 10, 2)->nullable();
            $table->integer('price_base')->nullable();
            $table->integer('rent_price')->nullable()->comment('Цена аренды');
            $table->integer('floor')->nullable();
            $table->json('images')->nullable();
            $table->json('raw_data')->nullable()->comment('Полные данные от unified API');
            $table->timestamps();
            
            $table->index('external_id');
            $table->index('complex_id');
            $table->index('price_base');
            $table->index('rent_price');
            $table->index('area_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trendagent_commercial');
    }
};
