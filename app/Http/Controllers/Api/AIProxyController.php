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
     * Process AI Request
     * 
     * Main endpoint for processing AI requests through Gemini or OpenAI.
     * Supports text generation, vision models, and file attachments.
     *
     * @OA\Post(
     *     path="/ai/process",
     *     summary="Process AI Request",
     *     description="Send a prompt to Gemini or OpenAI models with optional files. Returns AI-generated response with usage statistics and cost estimation.",
     *     operationId="processAIRequest",
     *     tags={"AI Processing"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="AI processing request with provider, model, prompt and optional parameters",
     *         @OA\JsonContent(
     *             required={"provider", "model", "prompt"},
     *             @OA\Property(
     *                 property="provider",
     *                 type="string",
     *                 enum={"gemini", "openai"},
     *                 description="AI provider to use",
     *                 example="gemini"
     *             ),
     *             @OA\Property(
     *                 property="model",
     *                 type="string",
     *                 description="Model name to use. Options: gemini-1.5-pro, gemini-1.5-flash, gemini-pro-vision, gpt-4-turbo-preview, gpt-4, gpt-3.5-turbo, gpt-4-vision-preview",
     *                 example="gemini-1.5-pro"
     *             ),
     *             @OA\Property(
     *                 property="prompt",
     *                 type="string",
     *                 description="Text prompt for the AI model",
     *                 example="Explain quantum computing in simple terms"
     *             ),
     *             @OA\Property(
     *                 property="user_api_key",
     *                 type="string",
     *                 description="Optional user-provided API key for the provider (if internal keys are disabled)",
     *                 example="AIzaSyBUwkCahleq..."
     *             ),
     *             @OA\Property(
     *                 property="use_saved_key",
     *                 type="boolean",
     *                 description="Use a saved user API key instead of internal key",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="saved_key_id",
     *                 type="integer",
     *                 description="ID of saved user API key (if use_saved_key is true)",
     *                 example=1
     *             ),
     *             @OA\Property(
     *                 property="parameters",
     *                 type="object",
     *                 description="Optional model parameters",
     *                 @OA\Property(property="temperature", type="number", format="float", description="Sampling temperature (0-2)", example=0.7),
     *                 @OA\Property(property="max_tokens", type="integer", description="Maximum tokens to generate", example=1000),
     *                 @OA\Property(property="top_p", type="number", format="float", description="Nucleus sampling (0-1)", example=0.9),
     *                 @OA\Property(property="top_k", type="integer", description="Top-k sampling (Gemini only)", example=40),
     *                 @OA\Property(property="stream", type="boolean", description="Stream response (future feature)", example=false)
     *             ),
     *             @OA\Property(
     *                 property="files",
     *                 type="array",
     *                 description="Optional files for vision models (base64 encoded)",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"type", "content"},
     *                     @OA\Property(property="type", type="string", enum={"image", "document"}, example="image"),
     *                     @OA\Property(property="content", type="string", description="Base64 encoded file content", example="data:image/png;base64,iVBORw0KGgoAAAANS..."),
     *                     @OA\Property(property="mime_type", type="string", example="image/png"),
     *                     @OA\Property(property="name", type="string", example="example.png")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="object",
     *                 description="Optional metadata for tracking",
     *                 @OA\Property(property="client_id", type="string", example="web-app"),
     *                 @OA\Property(property="session_id", type="string", example="session-123")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful AI response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="request_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="provider", type="string", example="gemini"),
     *                 @OA\Property(property="model", type="string", example="gemini-1.5-pro"),
     *                 @OA\Property(
     *                     property="response",
     *                     type="object",
     *                     @OA\Property(property="text", type="string", example="Quantum computing uses quantum bits..."),
     *                     @OA\Property(property="finish_reason", type="string", example="stop")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="usage",
     *                 type="object",
     *                 @OA\Property(property="prompt_tokens", type="integer", example=10),
     *                 @OA\Property(property="completion_tokens", type="integer", example=50),
     *                 @OA\Property(property="total_tokens", type="integer", example=60),
     *                 @OA\Property(property="estimated_cost", type="number", format="float", example=0.00075)
     *             ),
     *             @OA\Property(
     *                 property="metadata",
     *                 type="object",
     *                 @OA\Property(property="processing_time", type="number", format="float", example=2.345),
     *                 @OA\Property(property="timestamp", type="string", format="date-time", example="2026-02-06T15:30:00Z"),
     *                 @OA\Property(property="api_key_source", type="string", enum={"internal", "user_request", "user_saved"}, example="internal")
     *             ),
     *             @OA\Property(
     *                 property="limits",
     *                 type="object",
     *                 @OA\Property(property="daily_requests_used", type="integer", example=45),
     *                 @OA\Property(property="daily_requests_limit", type="integer", example=100),
     *                 @OA\Property(property="daily_requests_remaining", type="integer", example=55)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing bearer token",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The provider field is required."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="provider",
     *                     type="array",
     *                     @OA\Items(type="string", example="The provider field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Rate Limit Exceeded",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="type", type="string", example="RateLimitExceededException"),
     *                 @OA\Property(property="message", type="string", example="Daily request limit of 100 exceeded. Resets tomorrow.")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="request_id", type="string", format="uuid"),
     *             @OA\Property(property="error", type="object",
     *                 @OA\Property(property="type", type="string", example="AIProviderException"),
     *                 @OA\Property(property="message", type="string", example="AI provider error")
     *             )
     *         )
     *     )
     * )
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
