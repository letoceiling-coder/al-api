<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserUsageStats extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'total_requests',
        'successful_requests',
        'failed_requests',
        'total_tokens',
        'total_cost',
        'gemini_requests',
        'openai_requests',
    ];

    protected $casts = [
        'date' => 'date',
        'total_requests' => 'integer',
        'successful_requests' => 'integer',
        'failed_requests' => 'integer',
        'total_tokens' => 'integer',
        'total_cost' => 'decimal:4',
        'gemini_requests' => 'integer',
        'openai_requests' => 'integer',
    ];

    /**
     * Get the user that owns the stats
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create stats for today
     */
    public static function getTodayStats(int $userId): self
    {
        return self::firstOrCreate(
            [
                'user_id' => $userId,
                'date' => now()->toDateString(),
            ],
            [
                'total_requests' => 0,
                'successful_requests' => 0,
                'failed_requests' => 0,
                'total_tokens' => 0,
                'total_cost' => 0,
                'gemini_requests' => 0,
                'openai_requests' => 0,
            ]
        );
    }

    /**
     * Increment request count
     */
    public function incrementRequests(string $provider, bool $success = true): void
    {
        $this->increment('total_requests');
        
        if ($success) {
            $this->increment('successful_requests');
        } else {
            $this->increment('failed_requests');
        }

        if ($provider === 'gemini') {
            $this->increment('gemini_requests');
        } elseif ($provider === 'openai') {
            $this->increment('openai_requests');
        }
    }

    /**
     * Add tokens and cost
     */
    public function addUsage(int $tokens, float $cost): void
    {
        $this->increment('total_tokens', $tokens);
        $this->increment('total_cost', $cost);
    }
}
