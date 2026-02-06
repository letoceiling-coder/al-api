<?php

namespace App\Services\Analytics;

use App\Models\User;
use App\Models\AIRequestLog;
use App\Models\UserUsageStats;
use Illuminate\Support\Str;

class UsageTracker
{
    public function __construct(
        protected CostCalculator $costCalculator
    ) {}

    /**
     * Track AI request
     * 
     * @param array $data
     * @return AIRequestLog
     */
    public function track(array $data): AIRequestLog
    {
        $requestId = $data['request_id'] ?? Str::uuid()->toString();

        // Calculate cost if tokens are provided
        $estimatedCost = null;
        if (isset($data['prompt_tokens']) && isset($data['completion_tokens'])) {
            $estimatedCost = $this->costCalculator->calculate(
                $data['provider'],
                $data['model'],
                $data['prompt_tokens'],
                $data['completion_tokens']
            );
        }

        // Create log entry
        $log = AIRequestLog::create([
            'user_id' => $data['user_id'],
            'request_id' => $requestId,
            'provider' => $data['provider'],
            'model' => $data['model'],
            'prompt_length' => $data['prompt_length'] ?? null,
            'has_files' => $data['has_files'] ?? false,
            'file_count' => $data['file_count'] ?? 0,
            'prompt_tokens' => $data['prompt_tokens'] ?? null,
            'completion_tokens' => $data['completion_tokens'] ?? null,
            'total_tokens' => $data['total_tokens'] ?? null,
            'estimated_cost' => $estimatedCost,
            'processing_time' => $data['processing_time'] ?? null,
            'status' => $data['status'] ?? 'success',
            'error_message' => $data['error_message'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'api_key_source' => $data['api_key_source'] ?? null,
        ]);

        // Update usage stats
        if (config('ai.features.analytics')) {
            $this->updateStats($log);
        }

        return $log;
    }

    /**
     * Update user usage statistics
     */
    protected function updateStats(AIRequestLog $log): void
    {
        $stats = UserUsageStats::getTodayStats($log->user_id);

        // Increment requests
        $stats->incrementRequests(
            $log->provider,
            $log->status === 'success'
        );

        // Add tokens and cost
        if ($log->total_tokens && $log->estimated_cost) {
            $stats->addUsage($log->total_tokens, $log->estimated_cost);
        }
    }

    /**
     * Get user statistics
     */
    public function getUserStats(User $user, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = UserUsageStats::where('user_id', $user->id);

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        $stats = $query->get();

        return [
            'total_requests' => $stats->sum('total_requests'),
            'successful_requests' => $stats->sum('successful_requests'),
            'failed_requests' => $stats->sum('failed_requests'),
            'total_tokens' => $stats->sum('total_tokens'),
            'total_cost' => $stats->sum('total_cost'),
            'gemini_requests' => $stats->sum('gemini_requests'),
            'openai_requests' => $stats->sum('openai_requests'),
            'daily_breakdown' => $stats->toArray(),
        ];
    }

    /**
     * Get request history
     */
    public function getRequestHistory(
        User $user,
        int $limit = 50,
        ?string $provider = null,
        ?string $status = null
    ): array {
        $query = AIRequestLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($provider) {
            $query->where('provider', $provider);
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get()->toArray();
    }
}
