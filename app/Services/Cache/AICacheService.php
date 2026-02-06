<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class AICacheService
{
    protected int $defaultTtl;
    protected bool $enabled;
    protected string $prefix = 'ai:response:';

    public function __construct()
    {
        $this->defaultTtl = (int) config('ai.cache.ttl', 3600);
        $this->enabled = (bool) config('ai.cache.enabled', true);
    }

    /**
     * Get cached AI response
     */
    public function get(string $provider, string $model, string $prompt, array $parameters = [], array $files = []): ?array
    {
        if (!$this->enabled) {
            return null;
        }

        $key = $this->generateKey($provider, $model, $prompt, $parameters, $files);
        
        try {
            $cached = Cache::get($key);
            
            if ($cached) {
                // Increment cache hit counter
                $this->incrementHitCounter();
                
                return array_merge($cached, [
                    'cached' => true,
                    'cached_at' => $cached['cached_at'] ?? null,
                ]);
            }
            
            // Increment cache miss counter
            $this->incrementMissCounter();
            
        } catch (\Exception $e) {
            \Log::error('Cache get error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Store AI response in cache
     */
    public function put(
        string $provider,
        string $model,
        string $prompt,
        array $response,
        array $parameters = [],
        array $files = [],
        ?int $ttl = null
    ): bool {
        if (!$this->enabled) {
            return false;
        }

        // Don't cache if files are present (files make each request unique)
        if (!empty($files)) {
            return false;
        }

        $key = $this->generateKey($provider, $model, $prompt, $parameters, $files);
        $ttl = $ttl ?? $this->defaultTtl;

        try {
            $dataToCache = array_merge($response, [
                'cached_at' => now()->toIso8601String(),
            ]);

            return Cache::put($key, $dataToCache, $ttl);
        } catch (\Exception $e) {
            \Log::error('Cache put error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate cache key
     */
    protected function generateKey(
        string $provider,
        string $model,
        string $prompt,
        array $parameters = [],
        array $files = []
    ): string {
        // Normalize parameters (remove null values, sort keys)
        $normalizedParams = $this->normalizeParameters($parameters);
        
        // Create hash from prompt and parameters
        $hash = md5(json_encode([
            'prompt' => trim($prompt),
            'parameters' => $normalizedParams,
            'has_files' => !empty($files),
        ]));

        return $this->prefix . "{$provider}:{$model}:{$hash}";
    }

    /**
     * Normalize parameters for consistent cache keys
     */
    protected function normalizeParameters(array $parameters): array
    {
        // Remove null values
        $filtered = array_filter($parameters, fn($value) => $value !== null);
        
        // Sort by keys
        ksort($filtered);
        
        // Round float values to avoid float precision issues
        foreach ($filtered as $key => $value) {
            if (is_float($value)) {
                $filtered[$key] = round($value, 2);
            }
        }

        return $filtered;
    }

    /**
     * Invalidate cache by pattern
     */
    public function invalidateByProvider(string $provider): int
    {
        return $this->invalidateByPattern("{$this->prefix}{$provider}:*");
    }

    /**
     * Invalidate cache by model
     */
    public function invalidateByModel(string $provider, string $model): int
    {
        return $this->invalidateByPattern("{$this->prefix}{$provider}:{$model}:*");
    }

    /**
     * Invalidate all AI cache
     */
    public function invalidateAll(): int
    {
        return $this->invalidateByPattern("{$this->prefix}*");
    }

    /**
     * Invalidate cache by pattern
     */
    protected function invalidateByPattern(string $pattern): int
    {
        try {
            $keys = Redis::keys($pattern);
            
            if (empty($keys)) {
                return 0;
            }

            return Redis::del($keys);
        } catch (\Exception $e) {
            \Log::error('Cache invalidation error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        try {
            $hits = (int) Redis::get('cache:stats:hits') ?: 0;
            $misses = (int) Redis::get('cache:stats:misses') ?: 0;
            $total = $hits + $misses;
            
            $hitRate = $total > 0 ? round(($hits / $total) * 100, 2) : 0;

            return [
                'hits' => $hits,
                'misses' => $misses,
                'total_requests' => $total,
                'hit_rate' => $hitRate,
                'enabled' => $this->enabled,
                'ttl' => $this->defaultTtl,
            ];
        } catch (\Exception $e) {
            \Log::error('Cache stats error: ' . $e->getMessage());
            return [
                'hits' => 0,
                'misses' => 0,
                'total_requests' => 0,
                'hit_rate' => 0,
                'enabled' => $this->enabled,
                'ttl' => $this->defaultTtl,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Reset cache statistics
     */
    public function resetStats(): bool
    {
        try {
            Redis::del(['cache:stats:hits', 'cache:stats:misses']);
            return true;
        } catch (\Exception $e) {
            \Log::error('Cache reset stats error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Increment cache hit counter
     */
    protected function incrementHitCounter(): void
    {
        try {
            Redis::incr('cache:stats:hits');
        } catch (\Exception $e) {
            \Log::error('Cache hit counter error: ' . $e->getMessage());
        }
    }

    /**
     * Increment cache miss counter
     */
    protected function incrementMissCounter(): void
    {
        try {
            Redis::incr('cache:stats:misses');
        } catch (\Exception $e) {
            \Log::error('Cache miss counter error: ' . $e->getMessage());
        }
    }

    /**
     * Check if caching is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Enable caching
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable caching
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Get cache info (Redis info)
     */
    public function getCacheInfo(): array
    {
        try {
            $info = Redis::info();
            
            return [
                'redis_version' => $info['redis_version'] ?? 'unknown',
                'used_memory_human' => $info['used_memory_human'] ?? 'unknown',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'total_commands_processed' => $info['total_commands_processed'] ?? 0,
                'keyspace' => Redis::dbSize(),
            ];
        } catch (\Exception $e) {
            \Log::error('Cache info error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}
