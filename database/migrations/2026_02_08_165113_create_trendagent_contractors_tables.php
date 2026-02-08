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
        // Подрядчики
        Schema::create('trendagent_contractors', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 255)->unique();
            $table->string('name', 500);
            $table->text('description')->nullable();
            $table->text('logo_url')->nullable();
            $table->string('website', 500)->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('contact_email', 255)->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();
            
            $table->index('external_id');
        });

        // Проекты домов подрядчиков
        Schema::create('trendagent_contractor_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contractor_id')->constrained('trendagent_contractors')->onDelete('cascade');
            $table->string('external_id', 255)->unique();
            $table->string('guid', 255)->nullable()->comment('Slug');
            $table->string('name', 500);
            $table->text('description')->nullable();
            $table->integer('min_price')->nullable();
            $table->decimal('area_total', 10, 2)->nullable();
            $table->decimal('area_living', 10, 2)->nullable();
            $table->string('construction_time', 255)->nullable()->comment('Сроки строительства');
            $table->string('technology', 255)->nullable()->comment('Технология строительства');
            $table->json('images')->nullable();
            $table->json('raw_data')->nullable()->comment('Полные данные от unified API');
            $table->timestamps();
            
            $table->index('external_id');
            $table->index('guid');
            $table->index('contractor_id');
            $table->index('min_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trendagent_contractor_projects');
        Schema::dropIfExists('trendagent_contractors');
    }
};
