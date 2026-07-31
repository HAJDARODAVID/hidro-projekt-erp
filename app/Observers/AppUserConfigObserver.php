<?php

namespace App\Observers;

use App\Models\Application\AppUserConfig;
use Illuminate\Support\Facades\Redis;

class AppUserConfigObserver
{
    protected string $redisPrefix = 'user_config:';

    /**
     * Handle the AppUserConfig "created" event.
     */
    public function created(AppUserConfig $appUserConfig): void
    {
        $this->updateRedisCache($appUserConfig);
    }

    /**
     * Handle the AppUserConfig "updated" event.
     */
    public function updated(AppUserConfig $appUserConfig): void
    {
        $this->updateRedisCache($appUserConfig);
    }

    /**
     * Handle the AppUserConfig "deleted" event.
     */
    public function deleted(AppUserConfig $appUserConfig): void
    {
        $this->invalidateCache($appUserConfig);
    }

    /**
     * Handle the AppUserConfig "restored" event.
     */
    public function restored(AppUserConfig $appUserConfig): void
    {
        $this->updateRedisCache($appUserConfig);
    }

    /**
     * Handle the AppUserConfig "force deleted" event.
     */
    public function forceDeleted(AppUserConfig $appUserConfig): void
    {
        $this->invalidateCache($appUserConfig);
    }

    /**
     * Update Redis cache for the user config
     */
    private function updateRedisCache(AppUserConfig $appUserConfig): void
    {
        $cacheKey = $this->getCacheKey($appUserConfig);
        $value = $appUserConfig->getValue();

        try {
            // If value is null or empty, ensure cache is removed
            if ($value === null || $value === '') {
                Redis::del($cacheKey);
                return;
            }

            // Serialize and cache with TTL of 1 hour
            Redis::setex($cacheKey, 3600, json_encode($value));
        } catch (\Throwable $e) {
            // Don't interrupt the app flow from observer failures; log for debugging
            if (function_exists('report')) {
                report($e);
            }
        }
    }

    /**
     * Invalidate Redis cache for the user config
     */
    private function invalidateCache(AppUserConfig $appUserConfig): void
    {
        Redis::del($this->getCacheKey($appUserConfig));
    }

    /**
     * Build the Redis cache key for a user config entry
     */
    private function getCacheKey(AppUserConfig $appUserConfig): string
    {
        return "{$this->redisPrefix}{$appUserConfig->key}:{$appUserConfig->user_id}";
    }
}
