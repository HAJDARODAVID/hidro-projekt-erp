<?php

namespace App\Services\Config;

use App\Models\Application\AppUserConfig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

/**
 * Base class for per-user configuration services.
 *
 * Mirrors BaseConfigService, but scopes the value to a single user
 * instead of being global to the whole application.
 */
abstract class BaseUserConfigService
{
    protected string $configKey;
    protected string $redisPrefix = 'user_config:';
    protected int $cacheTTL = 3600; // 1 hour

    public function __construct()
    {
        if (!isset($this->configKey)) {
            throw new \Exception('configKey property must be defined in child service');
        }
    }

    /**
     * Get the config value for a user.
     * Tries Redis first, then the database.
     */
    public function getValue(?int $userId = null): mixed
    {
        $userId = $userId ?? Auth::id();

        $cacheKey = $this->getCacheKey($userId);
        $cached = Redis::get($cacheKey);
        if ($cached !== null) {
            return json_decode($cached, true);
        }

        $config = $this->getConfigFromDb($userId);
        if (!$config) {
            return null;
        }

        $value = $config->getValue();
        Redis::setex($cacheKey, $this->cacheTTL, json_encode($value));

        return $value;
    }

    /**
     * Set the config value for a user and refresh the cache.
     */
    public function setValue(mixed $value, ?int $userId = null): bool
    {
        $userId = $userId ?? Auth::id();
        $config = $this->getConfigFromDb($userId) ?? new AppUserConfig([
            'user_id' => $userId,
            'key' => $this->configKey,
        ]);

        $config->setValue($value);
        $success = $config->save();

        if ($success) {
            Redis::setex($this->getCacheKey($userId), $this->cacheTTL, json_encode($value));
        }

        return $success;
    }

    /**
     * Remove the user's stored config value, falling back to defaults.
     */
    public function resetValue(?int $userId = null): bool
    {
        $userId = $userId ?? Auth::id();
        $this->invalidateCache($userId);

        return $this->getConfigFromDb($userId)?->delete() ?? true;
    }

    protected function getConfigFromDb(int $userId): ?AppUserConfig
    {
        return AppUserConfig::where('user_id', $userId)->where('key', $this->configKey)->first();
    }

    protected function invalidateCache(int $userId): void
    {
        Redis::del($this->getCacheKey($userId));
    }

    protected function getCacheKey(int $userId): string
    {
        return "{$this->redisPrefix}{$this->configKey}:{$userId}";
    }
}
