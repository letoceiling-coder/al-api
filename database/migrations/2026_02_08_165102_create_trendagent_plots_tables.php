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
        // Поселки (для участков)
        Schema::create('trendagent_plot_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained('trendagent_regions')->onDelete('cascade');
            $table->string('external_id', 255)->unique();
            $table->string('guid', 255)->nullable()->comment('Slug');
            $table->string('name', 500);
            $table->text('address')->nullable();
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->json('images')->nullable();
            $table->json('raw_data')->nullable()->comment('Полные данные от unified API');
            $table->timestamps();
            
            $table->index('external_id');
            $table->index('guid');
            $table->index('region_id');
        });

        // Участки
        Schema::create('trendagent_plots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained('trendagent_regions')->onDelete('cascade');
            $table->foreignId('settlement_id')->constrained('trendagent_plot_settlements')->onDelete('cascade')->comment('Поселок');
            $table->string('external_id', 255)->unique();
            $table->string('number', 50)->nullable();
            $table->decimal('area', 10, 2)->comment('Площадь участка');
            $table->string('cadastral_number', 255)->nullable()->comment('Кадастровый номер');
            $table->integer('price_base')->nullable();
            $table->json('utilities')->nullable()->comment('Коммуникации');
            $table->json('raw_data')->nullable()->comment('Полные данные от unified API');
            $table->timestamps();
            
            $table->index('external_id');
            $table->index('settlement_id');
            $table->index('region_id');
            $table->index('price_base');
            $table->index('area');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trendagent_plots');
        Schema::dropIfExists('trendagent_plot_settlements');
    }
};
