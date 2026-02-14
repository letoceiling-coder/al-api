<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Добавляет last_seen_at, is_active для актуальности данных и деактивации.
     * Добавляет region_id в parkings и commercial для фильтрации.
     */
    public function up(): void
    {
        // region_id для parkings и commercial (фильтрация по городу)
        foreach (['trendagent_parkings', 'trendagent_commercial'] as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'region_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $after = $tableName === 'trendagent_parkings' ? 'complex_id' : 'complex_id';
                    $table->foreignId('region_id')->nullable()->after($after)
                        ->constrained('trendagent_regions')->onDelete('set null');
                });
            }
        }

        $tables = [
            'trendagent_complexes',
            'trendagent_parkings',
            'trendagent_houses',
            'trendagent_plot_settlements',
            'trendagent_plots',
            'trendagent_commercial',
            'trendagent_contractor_projects',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'last_seen_at')) {
                    $table->timestamp('last_seen_at')->nullable()->after('updated_at');
                }
                if (!Schema::hasColumn($tableName, 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('last_seen_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Удаляем region_id
        foreach (['trendagent_parkings', 'trendagent_commercial'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'region_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['region_id']);
                    $table->dropColumn('region_id');
                });
            }
        }

        $tables = [
            'trendagent_complexes',
            'trendagent_parkings',
            'trendagent_houses',
            'trendagent_plot_settlements',
            'trendagent_plots',
            'trendagent_commercial',
            'trendagent_contractor_projects',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'last_seen_at')) {
                    $table->dropColumn('last_seen_at');
                }
                if (Schema::hasColumn($tableName, 'is_active')) {
                    $table->dropColumn('is_active');
                }
            });
        }
    }
};
