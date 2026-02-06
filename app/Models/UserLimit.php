<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLimit extends Model
{
    protected $fillable = [
        'user_id',
        'daily_request_limit',
        'monthly_token_limit',
        'max_file_size_mb',
        'today_requests',
        'today_reset_at',
    ];

    protected $casts = [
        'daily_request_limit' => 'integer',
        'monthly_token_limit' => 'integer',
        'max_file_size_mb' => 'integer',
        'today_requests' => 'integer',
        'today_reset_at' => 'date',
    ];

    /**
     * Get the user that owns the limits
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create limits for user
     */
    public static function getForUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'daily_request_limit' => config('ai.limits.daily_requests', 100),
                'monthly_token_limit' => config('ai.limits.monthly_tokens', 100000),
                'max_file_size_mb' => config('ai.limits.max_file_size_mb', 10),
                'today_requests' => 0,
                'today_reset_at' => now()->toDateString(),
            ]
        );
    }

    /**
     * Check if daily limit is exceeded
     */
    public function isDailyLimitExceeded(): bool
    {
        $this->resetIfNewDay();
        return $this->today_requests >= $this->daily_request_limit;
    }

    /**
     * Increment today's request count
     */
    public function incrementTodayRequests(): void
    {
        $this->resetIfNewDay();
        $this->increment('today_requests');
    }

    /**
     * Reset daily counter if new day
     */
    protected function resetIfNewDay(): void
    {
        $today = now()->toDateString();
        
        if ($this->today_reset_at?->toDateString() !== $today) {
            $this->update([
                'today_requests' => 0,
                'today_reset_at' => $today,
            ]);
            $this->refresh();
        }
    }

    /**
     * Get remaining requests for today
     */
    public function getRemainingRequestsAttribute(): int
    {
        $this->resetIfNewDay();
        return max(0, $this->daily_request_limit - $this->today_requests);
    }
}
