<?php

namespace App\Observers;

use App\Models\Application\AppConfig;
use Illuminate\Support\Facades\Redis;

class AppConfigObserver
{
    protected string $redisPrefix = 'app_config:';

    /**
     * Handle the AppConfig "created" event.
     */
    public function created(AppConfig $appConfig): void
    {
        $this->updateRedisCache($appConfig);
    }

    /**
     * Handle the AppConfig "updated" event.
     */
    public function updated(AppConfig $appConfig): void
    {
        $this->updateRedisCache($appConfig);
    }

    /**
     * Handle the AppConfig "deleted" event.
     */
    public function deleted(AppConfig $appConfig): void
    {
        $this->invalidateCache($appConfig->key);
    }

    /**
     * Handle the AppConfig "restored" event.
     */
    public function restored(AppConfig $appConfig): void
    {
        $this->updateRedisCache($appConfig);
    }

    /**
     * Handle the AppConfig "force deleted" event.
     */
    public function forceDeleted(AppConfig $appConfig): void
    {
        $this->invalidateCache($appConfig->key);
    }

    /**
     * Update Redis cache for the config
     */
    private function updateRedisCache(AppConfig $appConfig): void
    {
        $cacheKey = $this->redisPrefix . $appConfig->key;
        $value = $appConfig->getValue();

        // Cache with TTL of 1 hour
        Redis::setex($cacheKey, 3600, $this->serializeValue($value));
    }

    /**
     * Invalidate Redis cache for the config
     */
    private function invalidateCache(string $key): void
    {
        $cacheKey = $this->redisPrefix . $key;
        Redis::del($cacheKey);
    }

    /**
     * Serialize value for Redis storage
     */
    private function serializeValue(mixed $value): string
    {
        return is_array($value) ? json_encode($value) : (string) $value;
    }
}
