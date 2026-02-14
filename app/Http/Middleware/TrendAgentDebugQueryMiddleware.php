<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TrendAgentDebugQueryMiddleware
{
    private int $queryCount = 0;
    private float $startTime;

    public function handle(Request $request, Closure $next): Response
    {
        if (!config('trendagent.debug_query', false)) {
            return $next($request);
        }

        if (!str_contains($request->path(), 'trendagent')) {
            return $next($request);
        }

        $this->queryCount = 0;
        $this->startTime = microtime(true);

        DB::listen(function () {
            $this->queryCount++;
        });

        $response = $next($request);

        $duration = round((microtime(true) - $this->startTime) * 1000);

        $response->headers->set('X-TrendAgent-Queries', (string) $this->queryCount);
        $response->headers->set('X-TrendAgent-QueryTime', $duration . 'ms');

        return $response;
    }
}
