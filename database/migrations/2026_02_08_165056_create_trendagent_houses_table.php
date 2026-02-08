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
        Schema::create('trendagent_houses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained('trendagent_regions')->onDelete('cascade');
            $table->string('external_id', 255)->unique();
            $table->string('guid', 255)->nullable()->comment('Slug');
            $table->string('name', 500)->nullable();
            $table->text('address')->nullable();
            $table->decimal('land_area', 10, 2)->nullable()->comment('Площадь участка');
            $table->decimal('house_area', 10, 2)->nullable()->comment('Площадь дома');
            $table->integer('floors_count')->nullable();
            $table->integer('rooms_count')->nullable();
            $table->integer('price_base')->nullable();
            $table->json('images')->nullable();
            $table->json('raw_data')->nullable()->comment('Полные данные от unified API');
            $table->timestamps();
            
            $table->index('external_id');
            $table->index('guid');
            $table->index('region_id');
            $table->index('price_base');
            $table->index('land_area');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trendagent_houses');
    }
};
