<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Analytics\UsageTracker;
use App\Models\UserLimit;
use App\Models\AIRequestLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function __construct(
        protected UsageTracker $usageTracker
    ) {}

    /**
     * Get overall summary
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $dateRange = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $stats = $this->usageTracker->getUserStats(
            $user,
            $dateRange['start_date'] ?? null,
            $dateRange['end_date'] ?? null
        );

        return response()->json([
            'success' => true,
            'summary' => $stats,
        ]);
    }

    /**
     * Get request history
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $filters = $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
            'provider' => 'nullable|in:gemini,openai',
            'status' => 'nullable|in:success,error,rate_limited',
        ]);

        $history = $this->usageTracker->getRequestHistory(
            $user,
            $filters['limit'] ?? 50,
            $filters['provider'] ?? null,
            $filters['status'] ?? null
        );

        return response()->json([
            'success' => true,
            'history' => $history,
        ]);
    }

    /**
     * Get cost breakdown
     */
    public function costs(Request $request): JsonResponse
    {
        $user = $request->user();

        // Last 30 days costs by day
        $costs = AIRequestLog::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, SUM(estimated_cost) as cost, COUNT(*) as requests')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        $total = $costs->sum('cost');

        return response()->json([
            'success' => true,
            'costs' => [
                'total_30_days' => round($total, 2),
                'daily_breakdown' => $costs,
            ],
        ]);
    }

    /**
     * Get current limits
     */
    public function limits(Request $request): JsonResponse
    {
        $user = $request->user();
        $limits = UserLimit::getForUser($user->id);

        return response()->json([
            'success' => true,
            'limits' => [
                'daily_requests' => [
                    'limit' => $limits->daily_request_limit,
                    'used' => $limits->today_requests,
                    'remaining' => $limits->remaining_requests,
                    'resets_at' => now()->endOfDay()->toIso8601String(),
                ],
                'monthly_tokens' => [
                    'limit' => $limits->monthly_token_limit,
                ],
                'max_file_size_mb' => $limits->max_file_size_mb,
            ],
        ]);
    }

    /**
     * Get stats by provider
     */
    public function byProvider(Request $request): JsonResponse
    {
        $user = $request->user();

        $stats = AIRequestLog::where('user_id', $user->id)
            ->selectRaw('provider, COUNT(*) as requests, SUM(total_tokens) as tokens, SUM(estimated_cost) as cost')
            ->groupBy('provider')
            ->get();

        return response()->json([
            'success' => true,
            'by_provider' => $stats,
        ]);
    }

    /**
     * Get stats by model
     */
    public function byModel(Request $request): JsonResponse
    {
        $user = $request->user();

        $stats = AIRequestLog::where('user_id', $user->id)
            ->selectRaw('provider, model, COUNT(*) as requests, SUM(total_tokens) as tokens, SUM(estimated_cost) as cost')
            ->groupBy('provider', 'model')
            ->orderBy('requests', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'by_model' => $stats,
        ]);
    }
}
