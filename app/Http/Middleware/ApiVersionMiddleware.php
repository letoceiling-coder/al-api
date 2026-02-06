<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiVersionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $version  The API version (e.g., 'v1', 'v2')
     */
    public function handle(Request $request, Closure $next, string $version = 'v1'): Response
    {
        // Set API version in request attributes
        $request->attributes->set('api_version', $version);
        
        // Process request
        $response = $next($request);
        
        // Add API version header to response
        if ($response instanceof \Illuminate\Http\JsonResponse || 
            $response instanceof \Illuminate\Http\Response) {
            $response->header('X-API-Version', $version);
            $response->header('X-API-Documentation', url('/api/documentation'));
        }
        
        return $response;
    }
}
