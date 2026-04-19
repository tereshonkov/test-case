<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Closure;

class RememberHelper
{
    public function rememberTagged(
        string $domain,
        string $key,
        int $ttl,
        array $tags,
        Closure $resolver,
    ): mixed {
        $ttlWithJitter = $ttl + random_int(1, max(1, (int) ceil($ttl * 0.1)));
        $computed = false;
        $start = hrtime(true);
    
        try {
            $result = Cache::lock("lock:{$key}", 5)->block(2, function () use ($key, $ttlWithJitter, $tags, $resolver, &$computed) {
                return Cache::tags($tags)->remember($key, $ttlWithJitter, function () use ($resolver, &$computed) {
                    $computed = true;
                    return $resolver();
                });
            });
        } catch (\Throwable $e) {
            Log::warning('cache.lock_fallback', ['key' => $key, 'error' => $e->getMessage()]);
            $result = Cache::tags($tags)->remember($key, $ttlWithJitter, function () use ($resolver, &$computed) {
                $computed = true;
                return $resolver();
            });
        }
    
        $durationMs = (hrtime(true) - $start) / 1_000_000;
        $hit = !$computed;
    
        Log::info('cache.read', [
            'domain' => $domain,
            'key' => $key,
            'hit' => $hit,
            'ttl' => $ttlWithJitter,
            'duration_ms' => round($durationMs, 2),
        ]);
    
        $metricKey = 'metrics:cache:' . $domain . ':' . now()->format('Ymd');
        Redis::hincrby($metricKey, $hit ? 'hit' : 'miss', 1);
        Redis::expire($metricKey, 172800);
    
        return $result;
    }
}