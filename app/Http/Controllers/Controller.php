<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="AL API Gateway",
 *     description="Unified API Gateway for Gemini and OpenAI models with token-based authentication, analytics, and user key management.",
 *     @OA\Contact(
 *         email="support@siteaccess.ru"
 *     ),
 *     @OA\License(
 *         name="Proprietary",
 *         url="https://api.siteaccess.ru/license"
 *     )
 * )
 *
 * @OA\Server(
 *     url="https://api.siteaccess.ru/api/v1",
 *     description="Production API Server (v1)"
 * )
 *
 * @OA\Server(
 *     url="https://api.siteaccess.ru/api",
 *     description="Legacy API Server (deprecated)"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="token",
 *     description="Laravel Sanctum Bearer Token Authentication. Obtain token via /api/v1/auth/login or create via artisan command."
 * )
 *
 * @OA\Tag(
 *     name="AI Processing",
 *     description="Main AI processing endpoints for Gemini and OpenAI"
 * )
 *
 * @OA\Tag(
 *     name="User API Keys",
 *     description="Manage user-provided AI API keys (Gemini, OpenAI)"
 * )
 *
 * @OA\Tag(
 *     name="Analytics",
 *     description="Usage analytics, costs, limits, and statistics"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication and token management"
 * )
 */
abstract class Controller
{
    //
}
