<?php

namespace App\Http\Controllers\TrendAgent;

use App\Http\Controllers\Controller;
use App\Models\TrendAgent\Apartment;
use App\Models\TrendAgent\Complex;
use App\Models\TrendAgent\Parking;
use App\Models\TrendAgent\SyncRun;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;

class HealthController extends Controller
{
    public function index(): JsonResponse
    {
        $lastSyncRuns = [];
        $counts = [];
        $version = null;

        try {
            if (class_exists(SyncRun::class)) {
                $lastSyncRuns = SyncRun::query()
                    ->orderByDesc('started_at')
                    ->limit(10)
                    ->get()
                    ->map(fn ($r) => [
                        'region' => $r->region,
                        'type' => $r->type,
                        'started_at' => $r->started_at?->toIso8601String(),
                        'finished_at' => $r->finished_at?->toIso8601String(),
                        'status' => $r->status,
                        'created_count' => $r->created_count,
                        'updated_count' => $r->updated_count,
                        'error_count' => $r->error_count,
                        'duration_ms' => $r->duration_ms,
                    ])
                    ->all();
            }

            $tables = [
                'apartments' => Apartment::class,
                'complexes' => Complex::class,
                'parkings' => Parking::class,
            ];

            foreach ($tables as $key => $model) {
                try {
                    $table = (new $model)->getTable();
                    $total = $model::count();
                    $active = Schema::hasColumn($table, 'is_active')
                        ? (int) $model::where('is_active', true)->count()
                        : $total;
                    $counts[$key] = ['active' => $active, 'total' => $total];
                } catch (\Throwable) {
                    $counts[$key] = ['active' => 0, 'total' => 0];
                }
            }

            if (function_exists('shell_exec') && is_dir(base_path('.git'))) {
                $hash = @shell_exec('git rev-parse --short HEAD 2>/dev/null');
                if ($hash) {
                    $version = trim($hash);
                }
            }
        } catch (\Throwable $e) {
            // continue with partial data
        }

        return response()->json([
            'ok' => true,
            'last_sync_runs' => $lastSyncRuns,
            'counts' => $counts,
            'version' => $version,
        ]);
    }
}
