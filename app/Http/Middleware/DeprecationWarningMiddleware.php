<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeprecationWarningMiddleware
{
    /**
     * Handle an incoming request and add deprecation warnings.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Process request
        $response = $next($request);
        
        // Add deprecation headers
        if ($response instanceof \Illuminate\Http\JsonResponse || 
            $response instanceof \Illuminate\Http\Response) {
            
            $response->header('X-API-Deprecated', 'true');
            $response->header('X-API-Deprecation-Date', '2026-04-01');
            $response->header('X-API-Sunset-Date', '2026-06-01');
            $response->header('X-API-Migration-Guide', url('/api/documentation#migration'));
            $response->header('Warning', '299 - "This endpoint is deprecated. Please migrate to /api/v1/* endpoints. See documentation for details."');
            
            // Add deprecation info to JSON responses
            if ($response instanceof \Illuminate\Http\JsonResponse) {
                $data = $response->getData(true);
                $data['_deprecation'] = [
                    'deprecated' => true,
                    'message' => 'This endpoint is deprecated and will be removed on 2026-06-01',
                    'alternative' => str_replace('/api/', '/api/v1/', $request->path()),
                    'documentation' => url('/api/documentation'),
                    'migration_guide' => url('/api/documentation#migration'),
                ];
                $response->setData($data);
            }
        }
        
        return $response;
    }
}
