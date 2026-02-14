<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Добавляет region_id для фильтрации по city/region (контракт db_api_contract.md).
     * ImportDataCommand уже устанавливает region_id при импорте.
     */
    public function up(): void
    {
        Schema::table('trendagent_apartments', function (Blueprint $table) {
            if (!Schema::hasColumn('trendagent_apartments', 'region_id')) {
                $table->foreignId('region_id')
                    ->nullable()
                    ->after('floor_id')
                    ->constrained('trendagent_regions')
                    ->onDelete('set null')
                    ->comment('Регион для фильтрации по city');
            }
        });

        Schema::table('trendagent_apartments', function (Blueprint $table) {
            if (Schema::hasColumn('trendagent_apartments', 'region_id')) {
                $table->index(['region_id', 'price_base'], 'idx_apt_region_price');
                $table->index(['region_id', 'rooms'], 'idx_apt_region_rooms');
                $table->index(['region_id', 'area_total'], 'idx_apt_region_area');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trendagent_apartments', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
        });

        Schema::table('trendagent_apartments', function (Blueprint $table) {
            $table->dropIndex('idx_apt_region_price');
            $table->dropIndex('idx_apt_region_rooms');
            $table->dropIndex('idx_apt_region_area');
        });

        Schema::table('trendagent_apartments', function (Blueprint $table) {
            $table->dropColumn('region_id');
        });
    }
};
