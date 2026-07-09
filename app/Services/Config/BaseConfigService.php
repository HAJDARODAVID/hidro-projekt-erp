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
    protected array $allowedDataTypes = ['string', 'integer', 'boolean', 'json', 'color', 'url', 'email', 'decimal'];

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
     * Create a new config record in database
     */
    public function createConfig(
        mixed $value,
        string $key = null,
        mixed $defaultValue = null,
        ?string $label = null,
        ?string $description = null,
        string $dataType = 'string',
        bool $isPublic = true,
        bool $isLocked = false,
        ?int $userId = null
    ): AppConfig {
        $key = $key ?? ($this->configKeys ?? null);

        if (is_array($key)) {
            throw new \Exception('Provide a single key for createConfig()');
        }

        if (!in_array($dataType, $this->allowedDataTypes, true)) {
            throw new \Exception("Invalid data type '{$dataType}' for createConfig()");
        }

        if ($this->getConfigFromDb($key)) {
            throw new \Exception("Configuration '{$key}' already exists");
        }

        if (is_array($value)) $dataType = 'json';

        $userId = $userId ?? Auth::id();
        $defaultValue = $defaultValue ?? $value;

        $config = new AppConfig();
        $config->key = $key;
        $config->label = $label;
        $config->description = $description;
        $config->data_type = $dataType;
        $config->is_public = $isPublic;
        $config->is_locked = $isLocked;
        $config->created_by = $userId;
        $config->updated_by = $userId;
        $config->value = $this->formatValueForStorage($value, $dataType);
        $config->default_value = $this->formatValueForStorage($defaultValue, $dataType);
        $config->save();

        return $config;
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
     * Convert value to database storage format by data type
     */
    protected function formatValueForStorage(mixed $value, string $dataType): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($dataType === 'json') {
            return is_string($value) ? $value : json_encode($value);
        }

        if ($dataType === 'boolean') {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
        }

        if (is_array($value)) {
            throw new \Exception("Array value requires json data type");
        }

        return (string) $value;
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
