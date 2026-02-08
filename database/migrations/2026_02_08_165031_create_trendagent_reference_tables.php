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
        // Типы отделки
        Schema::create('trendagent_finishing_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->timestamps();
            $table->index('code');
        });

        // Статусы объектов
        Schema::create('trendagent_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->string('type', 50)->nullable()->comment('Тип объекта (apartment, parking, house, plot, commercial)');
            $table->timestamps();
            $table->index('code');
            $table->index('type');
        });

        // Типы паркингов
        Schema::create('trendagent_parking_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->timestamps();
            $table->index('code');
        });

        // Типы коммерческой недвижимости
        Schema::create('trendagent_commercial_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->timestamps();
            $table->index('code');
        });

        // Типы бизнеса для коммерции
        Schema::create('trendagent_business_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->timestamps();
            $table->index('code');
        });

        // Типы балконов
        Schema::create('trendagent_balcony_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->timestamps();
            $table->index('code');
        });

        // Типы видов из окон
        Schema::create('trendagent_view_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->timestamps();
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trendagent_view_types');
        Schema::dropIfExists('trendagent_balcony_types');
        Schema::dropIfExists('trendagent_business_types');
        Schema::dropIfExists('trendagent_commercial_types');
        Schema::dropIfExists('trendagent_parking_types');
        Schema::dropIfExists('trendagent_statuses');
        Schema::dropIfExists('trendagent_finishing_types');
    }
};
