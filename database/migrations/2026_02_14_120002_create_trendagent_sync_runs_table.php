<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Таблица для отчётов импорта (ImportDataCommand) и health/last_sync.
     */
    public function up(): void
    {
        Schema::create('trendagent_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('region', 50)->nullable()->comment('Код региона (spb, msk)');
            $table->string('type', 50)->nullable()->comment('Тип объектов: apartments, complexes, parkings, ...');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('status', 20)->default('running')->comment('running|success|failed');
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('flags')->nullable()->comment('download_images, batch, etc.');
            $table->text('error_summary')->nullable();
            $table->timestamps();

            $table->index('region', 'idx_sync_runs_region');
            $table->index('type', 'idx_sync_runs_type');
            $table->index('started_at', 'idx_sync_runs_started');
            $table->index('status', 'idx_sync_runs_status');
            $table->index(['region', 'type', 'started_at'], 'idx_sync_runs_region_type_started');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trendagent_sync_runs');
    }
};
