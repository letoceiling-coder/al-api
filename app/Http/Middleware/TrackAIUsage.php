<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackAIUsage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Before request - could add request timing, etc.
        $startTime = microtime(true);
        
        $response = $next($request);
        
        // After request - could add response headers, etc.
        if (config('ai.features.analytics')) {
            $processingTime = microtime(true) - $startTime;
            $response->headers->set('X-Processing-Time', round($processingTime, 3));
            $response->headers->set('X-Request-ID', $request->attributes->get('request_id', 'unknown'));
        }
        
        return $response;
    }
}
