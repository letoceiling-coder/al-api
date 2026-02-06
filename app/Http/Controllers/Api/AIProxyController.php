<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AIProcessRequest;
use App\Services\AI\ApiKeyResolver;
use App\Services\AI\GeminiService;
use App\Services\AI\OpenAIService;
use App\Services\Analytics\UsageTracker;
use App\Services\Analytics\CostCalculator;
use App\Models\UserLimit;
use App\Exceptions\RateLimitExceededException;
use App\Exceptions\AIProviderException;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

class AIProxyController extends Controller
{
    public function __construct(
        protected ApiKeyResolver $keyResolver,
        protected GeminiService $geminiService,
        protected OpenAIService $openaiService,
        protected UsageTracker $usageTracker,
        protected CostCalculator $costCalculator
    ) {}

    /**
     * Process AI request
     */
    public function process(AIProcessRequest $request): JsonResponse
    {
        $startTime = microtime(true);
        $user = $request->user();
        $requestId = Str::uuid()->toString();

        try {
            // 1. Check rate limits
            if (config('ai.features.rate_limiting')) {
                $this->checkRateLimits($user);
            }

            // 2. Validate provider and model
            $this->keyResolver->validateProvider($request->provider);
            
            // 3. Resolve API key
            $apiKeyInfo = $this->keyResolver->resolve(
                $request->provider,
                $user,
                $request->all()
            );

            // 4. Validate model and files
            $this->validateModelCapabilities(
                $request->provider,
                $request->model,
                !empty($request->files)
            );

            // 5. Process request through appropriate service
            $response = $this->executeAIRequest(
                $request->provider,
                $request->model,
                $apiKeyInfo['key'],
                $request->all()
            );

            // 6. Calculate metrics
            $processingTime = microtime(true) - $startTime;
            $estimatedCost = $this->costCalculator->calculate(
                $request->provider,
                $request->model,
                $response['usage']['prompt_tokens'] ?? 0,
                $response['usage']['completion_tokens'] ?? 0
            );

            // 7. Track usage
            if (config('ai.features.logging')) {
                $this->trackRequest([
                    'user_id' => $user->id,
                    'request_id' => $requestId,
                    'provider' => $request->provider,
                    'model' => $request->model,
                    'prompt_length' => strlen($request->prompt),
                    'has_files' => !empty($request->files),
                    'file_count' => count($request->files ?? []),
                    'prompt_tokens' => $response['usage']['prompt_tokens'] ?? null,
                    'completion_tokens' => $response['usage']['completion_tokens'] ?? null,
                    'total_tokens' => $response['usage']['total_tokens'] ?? null,
                    'processing_time' => $processingTime,
                    'status' => 'success',
                    'api_key_source' => $apiKeyInfo['source'],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }

            // 8. Get user limits
            $limits = UserLimit::getForUser($user->id);
            $limits->incrementTodayRequests();

            // 9. Return response
            return response()->json([
                'success' => true,
                'request_id' => $requestId,
                'data' => [
                    'provider' => $request->provider,
                    'model' => $request->model,
                    'response' => [
                        'text' => $response['text'],
                        'finish_reason' => $response['finish_reason'] ?? 'stop',
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => $response['usage']['prompt_tokens'] ?? 0,
                    'completion_tokens' => $response['usage']['completion_tokens'] ?? 0,
                    'total_tokens' => $response['usage']['total_tokens'] ?? 0,
                    'estimated_cost' => $estimatedCost,
                ],
                'metadata' => [
                    'processing_time' => round($processingTime, 3),
                    'timestamp' => now()->toIso8601String(),
                    'api_key_source' => $apiKeyInfo['source'],
                ],
                'limits' => [
                    'daily_requests_used' => $limits->today_requests,
                    'daily_requests_limit' => $limits->daily_request_limit,
                    'daily_requests_remaining' => $limits->remaining_requests,
                ],
            ]);

        } catch (RateLimitExceededException $e) {
            // Track failed request
            $this->trackFailedRequest($user->id, $requestId, $request, 'rate_limited', $e->getMessage());
            throw $e;

        } catch (\Exception $e) {
            // Track failed request
            $this->trackFailedRequest($user->id, $requestId, $request, 'error', $e->getMessage());

            return response()->json([
                'success' => false,
                'request_id' => $requestId,
                'error' => [
                    'type' => class_basename($e),
                    'message' => $e->getMessage(),
                ],
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Execute AI request through appropriate service
     */
    protected function executeAIRequest(
        string $provider,
        string $model,
        string $apiKey,
        array $data
    ): array {
        $service = match ($provider) {
            'gemini' => $this->geminiService,
            'openai' => $this->openaiService,
            default => throw new AIProviderException("Unsupported provider: {$provider}"),
        };

        // TODO: Implement actual API calls in services
        // For now, return mock data
        return [
            'text' => 'Mock response from ' . $provider . ' ' . $model,
            'finish_reason' => 'stop',
            'usage' => [
                'prompt_tokens' => strlen($data['prompt'] ?? '') / 4, // Rough estimate
                'completion_tokens' => 50,
                'total_tokens' => (strlen($data['prompt'] ?? '') / 4) + 50,
            ],
        ];
    }

    /**
     * Check rate limits
     */
    protected function checkRateLimits($user): void
    {
        $limits = UserLimit::getForUser($user->id);

        if ($limits->isDailyLimitExceeded()) {
            throw new RateLimitExceededException(
                "Daily request limit of {$limits->daily_request_limit} exceeded. Resets tomorrow.",
                86400 // 24 hours
            );
        }
    }

    /**
     * Validate model capabilities
     */
    protected function validateModelCapabilities(
        string $provider,
        string $model,
        bool $hasFiles
    ): void {
        if ($hasFiles && !$this->costCalculator->supportsVision($provider, $model)) {
            throw new AIProviderException(
                "Model {$model} does not support file/vision inputs"
            );
        }
    }

    /**
     * Track successful request
     */
    protected function trackRequest(array $data): void
    {
        try {
            $this->usageTracker->track($data);
        } catch (\Exception $e) {
            // Log error but don't fail the request
            logger()->error('Failed to track usage', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
        }
    }

    /**
     * Track failed request
     */
    protected function trackFailedRequest(
        int $userId,
        string $requestId,
        $request,
        string $status,
        string $errorMessage
    ): void {
        try {
            $this->usageTracker->track([
                'user_id' => $userId,
                'request_id' => $requestId,
                'provider' => $request->provider ?? 'unknown',
                'model' => $request->model ?? 'unknown',
                'prompt_length' => strlen($request->prompt ?? ''),
                'has_files' => !empty($request->files),
                'file_count' => count($request->files ?? []),
                'status' => $status,
                'error_message' => $errorMessage,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Exception $e) {
            logger()->error('Failed to track failed request', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
