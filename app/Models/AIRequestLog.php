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
        'model_version',
        'prompt_length',
        'has_files',
        'file_count',
        'file_metadata',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'estimated_cost',
        'estimated_cost_usd',
        'processing_time',
        'queue_time_ms',
        'ai_response_time_ms',
        'network_time_ms',
        'status',
        'error_message',
        'error_code',
        'error_details',
        'error_type',
        'ip_address',
        'user_agent',
        'user_country',
        'user_timezone',
        'api_key_source',
        'finish_reason',
        'safety_ratings',
        'model_parameters_used',
        'request_size_bytes',
        'response_size_bytes',
        'was_cached',
        'used_streaming',
        'used_multipart',
    ];

    protected $casts = [
        'has_files' => 'boolean',
        'file_count' => 'integer',
        'file_metadata' => 'json',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'estimated_cost' => 'decimal:6',
        'estimated_cost_usd' => 'decimal:6',
        'processing_time' => 'float',
        'queue_time_ms' => 'float',
        'ai_response_time_ms' => 'float',
        'network_time_ms' => 'float',
        'safety_ratings' => 'json',
        'model_parameters_used' => 'json',
        'request_size_bytes' => 'integer',
        'response_size_bytes' => 'integer',
        'was_cached' => 'boolean',
        'used_streaming' => 'boolean',
        'used_multipart' => 'boolean',
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
