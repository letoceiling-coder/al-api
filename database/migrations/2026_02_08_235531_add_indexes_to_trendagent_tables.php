<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Для MySQL 5.7+ можно использовать виртуальные колонки для индексации JSON полей
        // Для оптимизации запросов с JSON_EXTRACT создаем виртуальные колонки
        
        // Для таблицы apartments - индексируем city.id из raw_data
        if (DB::getDriverName() === 'mysql') {
            // Проверяем, существует ли уже колонка
            $columns = DB::select("SHOW COLUMNS FROM trendagent_apartments LIKE 'city_id_extracted'");
            if (empty($columns)) {
                DB::statement("ALTER TABLE trendagent_apartments 
                    ADD COLUMN city_id_extracted VARCHAR(255) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(raw_data, '$.city.id'))) VIRTUAL,
                    ADD INDEX idx_city_id_extracted (city_id_extracted)");
            }
        }

        // Добавляем индексы для других часто используемых полей
        Schema::table('trendagent_apartments', function (Blueprint $table) {
            if (!Schema::hasColumn('trendagent_apartments', 'created_at_index')) {
                $table->index('created_at', 'idx_created_at');
            }
        });

        Schema::table('trendagent_complexes', function (Blueprint $table) {
            if (!Schema::hasColumn('trendagent_complexes', 'created_at_index')) {
                $table->index('created_at', 'idx_created_at');
            }
            if (!Schema::hasColumn('trendagent_complexes', 'region_id_index')) {
                $table->index('region_id', 'idx_region_id');
            }
        });

        Schema::table('trendagent_parkings', function (Blueprint $table) {
            if (!Schema::hasColumn('trendagent_parkings', 'created_at_index')) {
                $table->index('created_at', 'idx_created_at');
            }
            if (!Schema::hasColumn('trendagent_parkings', 'complex_id_index')) {
                $table->index('complex_id', 'idx_complex_id');
            }
        });

        Schema::table('trendagent_houses', function (Blueprint $table) {
            if (!Schema::hasColumn('trendagent_houses', 'created_at_index')) {
                $table->index('created_at', 'idx_created_at');
            }
            if (!Schema::hasColumn('trendagent_houses', 'region_id_index')) {
                $table->index('region_id', 'idx_region_id');
            }
        });

        Schema::table('trendagent_plots', function (Blueprint $table) {
            if (!Schema::hasColumn('trendagent_plots', 'created_at_index')) {
                $table->index('created_at', 'idx_created_at');
            }
            if (!Schema::hasColumn('trendagent_plots', 'region_id_index')) {
                $table->index('region_id', 'idx_region_id');
            }
        });

        Schema::table('trendagent_commercial', function (Blueprint $table) {
            if (!Schema::hasColumn('trendagent_commercial', 'created_at_index')) {
                $table->index('created_at', 'idx_created_at');
            }
            if (!Schema::hasColumn('trendagent_commercial', 'complex_id_index')) {
                $table->index('complex_id', 'idx_complex_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            $columns = DB::select("SHOW COLUMNS FROM trendagent_apartments LIKE 'city_id_extracted'");
            if (!empty($columns)) {
                DB::statement("ALTER TABLE trendagent_apartments DROP COLUMN city_id_extracted");
            }
        }

        Schema::table('trendagent_apartments', function (Blueprint $table) {
            $table->dropIndex('idx_created_at');
        });

        Schema::table('trendagent_complexes', function (Blueprint $table) {
            $table->dropIndex('idx_created_at');
            $table->dropIndex('idx_region_id');
        });

        Schema::table('trendagent_parkings', function (Blueprint $table) {
            $table->dropIndex('idx_created_at');
            $table->dropIndex('idx_complex_id');
        });

        Schema::table('trendagent_houses', function (Blueprint $table) {
            $table->dropIndex('idx_created_at');
            $table->dropIndex('idx_region_id');
        });

        Schema::table('trendagent_plots', function (Blueprint $table) {
            $table->dropIndex('idx_created_at');
            $table->dropIndex('idx_region_id');
        });

        Schema::table('trendagent_commercial', function (Blueprint $table) {
            $table->dropIndex('idx_created_at');
            $table->dropIndex('idx_complex_id');
        });
    }
};
