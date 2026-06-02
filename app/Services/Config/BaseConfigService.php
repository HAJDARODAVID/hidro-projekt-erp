<?php

namespace App\Services\Config;

use App\Models\Application\AppConfig;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Auth;

/**
 * @method void initConfig()
 */
abstract class BaseConfigService
{
    protected string|array $configKeys;
    protected string $redisPrefix = 'app_config:';
    protected int $cacheTTL = 3600; // 1 hour

    public function __construct()
    {
        if (!isset($this->configKeys)) {
            throw new \Exception('configKeys property must be defined in child service');
        }
        if (method_exists(get_class($this), 'initConfig')) $this->initConfig();
    }

    /**
     * Get a single config value
     * Tries Redis first, then Database, and caches to Redis if not found
     */
    public function getValue(string $key = null): mixed
    {
        $key = $key ?? ($this->configKeys ?? null);

        if (is_array($key)) {
            throw new \Exception('Use getValues() for multiple keys');
        }
        return $this->getValueFromCache($key);
    }

    /**
     * Get multiple config values
     */
    public function getValues(): array
    {
        $keys = is_array($this->configKeys) ? $this->configKeys : [$this->configKeys];
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->getValueFromCache($key);
        }

        return $values;
    }

    /**
     * Get default value for a config
     */
    public function getDefaultValue(string $key = null): mixed
    {
        $key = $key ?? ($this->configKeys ?? null);

        if (is_array($key)) {
            throw new \Exception('Provide a single key for getDefaultValue()');
        }

        $config = $this->getConfigFromDb($key);
        return $config ? $config->getDefaultValue() : null;
    }

    /**
     * Get the full config object from database
     */
    public function getConfig(string $key = null): ?AppConfig
    {
        $key = $key ?? ($this->configKeys ?? null);

        if (is_array($key)) {
            throw new \Exception('Provide a single key for getConfig()');
        }

        return $this->getConfigFromDb($key);
    }

    /**
     * Set a config value
     */
    public function setValue(string $value, string $key = null, ?int $userId = null): bool
    {
        $key = $key ?? ($this->configKeys ?? null);

        if (is_array($key)) {
            throw new \Exception('Provide a single key for setValue()');
        }

        $userId = $userId ?? Auth::id();
        $config = $this->getConfigFromDb($key);

        if (!$config) {
            return false;
        }

        if ($config->is_locked) {
            throw new \Exception("Configuration '{$key}' is locked and cannot be modified");
        }

        $config->setValue($value);
        $config->updated_by = $userId;
        $success = $config->save();

        if ($success) {
            $this->invalidateCache($key);
        }

        return $success;
    }

    /**
     * Reset config to default value
     */
    public function resetToDefault(string $key = null, ?int $userId = null): bool
    {
        $key = $key ?? ($this->configKeys ?? null);

        if (is_array($key)) {
            throw new \Exception('Provide a single key for resetToDefault()');
        }

        $userId = $userId ?? Auth::id();
        $config = $this->getConfigFromDb($key);

        if (!$config) {
            return false;
        }

        if ($config->is_locked) {
            throw new \Exception("Configuration '{$key}' is locked and cannot be modified");
        }

        $config->value = $config->default_value;
        $config->updated_by = $userId;
        $success = $config->save();

        if ($success) {
            $this->invalidateCache($key);
        }

        return $success;
    }

    /**
     * Check if config is modified from default
     */
    public function isModified(string $key = null): bool
    {
        $key = $key ?? ($this->configKeys ?? null);

        if (is_array($key)) {
            throw new \Exception('Provide a single key for isModified()');
        }

        $config = $this->getConfigFromDb($key);
        return $config ? $config->isModified() : false;
    }

    /**
     * Get who created this config
     */
    public function getCreatedBy(string $key = null)
    {
        $key = $key ?? ($this->configKeys ?? null);

        if (is_array($key)) {
            throw new \Exception('Provide a single key for getCreatedBy()');
        }

        $config = $this->getConfigFromDb($key);
        return $config?->createdBy;
    }

    /**
     * Get who last updated this config
     */
    public function getUpdatedBy(string $key = null)
    {
        $key = $key ?? ($this->configKeys ?? null);

        if (is_array($key)) {
            throw new \Exception('Provide a single key for getUpdatedBy()');
        }

        $config = $this->getConfigFromDb($key);
        return $config?->updatedBy;
    }

    /**
     * Get config metadata (creation/update info)
     */
    public function getMetadata(string $key = null): array
    {
        $key = $key ?? ($this->configKeys ?? null);

        if (is_array($key)) {
            throw new \Exception('Provide a single key for getMetadata()');
        }

        $config = $this->getConfigFromDb($key);

        if (!$config) {
            return [];
        }

        return [
            'key' => $config->key,
            'label' => $config->label,
            'description' => $config->description,
            'is_locked' => $config->is_locked,
            'is_public' => $config->is_public,
            'created_at' => $config->created_at,
            'updated_at' => $config->updated_at,
            'created_by' => $config->createdBy?->name,
            'updated_by' => $config->updatedBy?->name,
        ];
    }

    /**
     * Check if config is locked
     */
    public function isLocked(string $key = null): bool
    {
        $key = $key ?? ($this->configKeys ?? null);

        if (is_array($key)) {
            throw new \Exception('Provide a single key for isLocked()');
        }

        $config = $this->getConfigFromDb($key);
        return $config?->is_locked ?? false;
    }

    /**
     * Check if config is public (user editable)
     */
    public function isPublic(string $key = null): bool
    {
        $key = $key ?? ($this->configKeys ?? null);

        if (is_array($key)) {
            throw new \Exception('Provide a single key for isPublic()');
        }

        $config = $this->getConfigFromDb($key);
        return $config?->is_public ?? false;
    }

    /**
     * Get value from cache or database
     */
    protected function getValueFromCache(string $key): mixed
    {
        $cacheKey = $this->redisPrefix . $key;

        // Try to get from Redis
        $cached = Redis::get($cacheKey);
        if ($cached !== null) {
            return $this->unserializeValue($cached);
        }

        // Get from database
        $config = $this->getConfigFromDb($key);
        if (!$config) {
            return null;
        }

        $value = $config->getValue();

        // Cache to Redis
        Redis::setex($cacheKey, $this->cacheTTL, $this->serializeValue($value));

        return $value;
    }

    /**
     * Get config from database
     */
    protected function getConfigFromDb(string $key): ?AppConfig
    {
        return AppConfig::where('key', $key)->first();
    }

    /**
     * Invalidate cache for a key
     */
    protected function invalidateCache(string $key): void
    {
        $cacheKey = $this->redisPrefix . $key;
        Redis::del($cacheKey);
    }

    /**
     * Serialize value for Redis storage
     */
    protected function serializeValue(mixed $value): string
    {
        return is_array($value) ? json_encode($value) : (string) $value;
    }

    /**
     * Unserialize value from Redis
     */
    protected function unserializeValue(string $value): mixed
    {
        $decoded = json_decode($value, true);
        return $decoded !== null ? $decoded : $value;
    }

    /**
     * Clear all cache for this service
     */
    public function clearCache(): void
    {
        $keys = is_array($this->configKeys) ? $this->configKeys : [$this->configKeys];

        foreach ($keys as $key) {
            $this->invalidateCache($key);
        }
    }

    /**
     * Get cache TTL (time to live)
     */
    public function getCacheTTL(): int
    {
        return $this->cacheTTL;
    }

    /**
     * Set cache TTL
     */
    public function setCacheTTL(int $seconds): self
    {
        $this->cacheTTL = $seconds;
        return $this;
    }
}
