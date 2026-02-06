<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Illuminate\Support\Str;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'api_key',
        'user_api_key',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response (RFC 7807 format).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        // Only format as RFC 7807 for API requests
        if ($request->is('api/*') || $request->expectsJson()) {
            return $this->renderRFC7807($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Render exception in RFC 7807 Problem Details format
     */
    protected function renderRFC7807($request, Throwable $e): JsonResponse
    {
        $traceId = $request->header('X-Trace-ID') ?? Str::uuid()->toString();
        
        // Add trace ID to response headers
        $headers = [
            'X-Trace-ID' => $traceId,
            'X-API-Version' => $request->attributes->get('api_version', 'v1'),
            'Content-Type' => 'application/problem+json',
        ];

        // Determine error type and details based on exception
        $problem = $this->buildProblemDetails($request, $e, $traceId);

        return response()->json($problem, $problem['status'], $headers);
    }

    /**
     * Build RFC 7807 problem details
     */
    protected function buildProblemDetails($request, Throwable $e, string $traceId): array
    {
        $baseUrl = url('/docs/errors');
        
        // Default problem structure
        $problem = [
            'type' => $baseUrl . '/internal-error',
            'title' => 'Internal Server Error',
            'status' => 500,
            'detail' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred',
            'instance' => $request->path(),
            'trace_id' => $traceId,
            'timestamp' => now()->toIso8601String(),
        ];

        // Customize based on exception type
        if ($e instanceof ValidationException) {
            $problem = array_merge($problem, [
                'type' => $baseUrl . '/validation-error',
                'title' => 'Validation Error',
                'status' => 422,
                'detail' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ]);
        }
        elseif ($e instanceof AuthenticationException) {
            $problem = array_merge($problem, [
                'type' => $baseUrl . '/authentication-required',
                'title' => 'Authentication Required',
                'status' => 401,
                'detail' => 'Unauthenticated. Please provide a valid bearer token.',
            ]);
        }
        elseif ($e instanceof InvalidApiKeyException) {
            $problem = array_merge($problem, [
                'type' => $baseUrl . '/invalid-api-key',
                'title' => 'Invalid API Key',
                'status' => 401,
                'detail' => $e->getMessage(),
                'provider' => $e->getProvider(),
            ]);
        }
        elseif ($e instanceof RateLimitExceededException) {
            $problem = array_merge($problem, [
                'type' => $baseUrl . '/rate-limit-exceeded',
                'title' => 'Rate Limit Exceeded',
                'status' => 429,
                'detail' => $e->getMessage(),
                'retry_after' => $e->getRetryAfter(),
            ]);
        }
        elseif ($e instanceof AIProviderException) {
            $problem = array_merge($problem, [
                'type' => $baseUrl . '/ai-provider-error',
                'title' => 'AI Provider Error',
                'status' => $e->getCode() ?: 502,
                'detail' => $e->getMessage(),
                'provider' => $e->getProvider(),
            ]);
        }
        elseif ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            $problem = array_merge($problem, [
                'type' => $baseUrl . '/not-found',
                'title' => 'Not Found',
                'status' => 404,
                'detail' => 'The requested resource was not found.',
            ]);
        }
        elseif ($e instanceof HttpException) {
            $problem = array_merge($problem, [
                'type' => $baseUrl . '/http-error',
                'title' => class_basename($e),
                'status' => $e->getStatusCode(),
                'detail' => $e->getMessage() ?: 'An HTTP error occurred.',
            ]);
        }

        // Add stack trace in debug mode
        if (config('app.debug')) {
            $problem['debug'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(5)->map(function ($trace) {
                    return [
                        'file' => $trace['file'] ?? 'unknown',
                        'line' => $trace['line'] ?? 0,
                        'function' => $trace['function'] ?? 'unknown',
                    ];
                })->toArray(),
            ];
        }

        return $problem;
    }
}
