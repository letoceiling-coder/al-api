<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware для аутентификации TrendAgent API
 * Использует отдельный токен: 8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF
 */
class TrendAgentAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?? $request->header('Authorization');
        
        // Убираем префикс "Bearer " если есть
        if ($token && str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7);
        }
        
        $expectedToken = '8P3zhp#BA5y@o!iVs&oG44DzI2uWY4GF';
        
        if ($token !== $expectedToken) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid or missing TrendAgent API token.',
                'error' => [
                    'type' => 'UnauthorizedException',
                    'code' => 'TRENDAGENT_INVALID_TOKEN',
                ],
            ], 401);
        }
        
        return $next($request);
    }
}
