<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIRequestLog extends Model
{
    protected $fillable = [
        'user_id',
        'request_id',
        'provider',
        'model',
        'prompt_length',
        'has_files',
        'file_count',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'estimated_cost',
        'processing_time',
        'status',
        'error_message',
        'ip_address',
        'user_agent',
        'api_key_source',
    ];

    protected $casts = [
        'has_files' => 'boolean',
        'file_count' => 'integer',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'estimated_cost' => 'decimal:6',
        'processing_time' => 'float',
    ];

    /**
     * Get the user that made the request
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include successful requests
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope a query to only include failed requests
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'error');
    }

    /**
     * Scope a query for a specific provider
     */
    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope a query for a specific date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
