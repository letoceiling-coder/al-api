<?php

namespace App\Models\TrendAgent;

use Illuminate\Database\Eloquent\Model;

class SyncRun extends Model
{
    protected $table = 'trendagent_sync_runs';

    protected $fillable = [
        'region',
        'type',
        'started_at',
        'finished_at',
        'status',
        'created_count',
        'updated_count',
        'skipped_count',
        'error_count',
        'duration_ms',
        'flags',
        'error_summary',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'created_count' => 'integer',
        'updated_count' => 'integer',
        'skipped_count' => 'integer',
        'error_count' => 'integer',
        'duration_ms' => 'integer',
        'flags' => 'array',
    ];
}
