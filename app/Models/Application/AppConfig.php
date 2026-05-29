<?php

namespace App\Models\Application;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppConfig extends Model
{
    use HasFactory;
    protected $table = 'app_configs';

    protected $fillable = [
        'key',
        'value',
        'default_value',
        'label',
        'description',
        'data_type',
        'is_public',
        'is_locked',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_locked' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    /**
     * Get the user who created this config record.
     *
     * @return BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this config record.
     *
     * @return BelongsTo
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    /**
     * Scope query to only public configuration entries.
     *
     * @param  \\Illuminate\\Database\\Eloquent\\Builder  $query
     * @return \\Illuminate\\Database\\Eloquent\\Builder
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope query to only admin/private configuration entries.
     *
     * @param  \\Illuminate\\Database\\Eloquent\\Builder  $query
     * @return \\Illuminate\\Database\\Eloquent\\Builder
     */
    public function scopeAdmin($query)
    {
        return $query->where('is_public', false);
    }

    /**
     * Scope query to only unlocked configuration entries.
     *
     * @param  \\Illuminate\\Database\\Eloquent\\Builder  $query
     * @return \\Illuminate\\Database\\Eloquent\\Builder
     */
    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    /**
     * Scope query to only locked configuration entries.
     *
     * @param  \\Illuminate\\Database\\Eloquent\\Builder  $query
     * @return \\Illuminate\\Database\\Eloquent\\Builder
     */
    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    // Accessors & Mutators
    /**
     * Return the stored config value converted to its configured data type.
     *
     * @return mixed
     */
    public function getValue()
    {
        if ($this->data_type === 'json' && $this->value) {
            return json_decode($this->value, true);
        }

        if ($this->data_type === 'boolean' && $this->value) {
            return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);
        }

        if ($this->data_type === 'integer') {
            return (int) $this->value;
        }

        if ($this->data_type === 'decimal') {
            return (float) $this->value;
        }

        return $this->value;
    }

    /**
     * Set the config value while preserving the proper storage format.
     *
     * @param  mixed  $value
     * @return $this
     */
    public function setValue($value)
    {
        if ($this->data_type === 'json' && is_array($value)) {
            $this->value = json_encode($value);
        } elseif ($this->data_type === 'boolean') {
            $this->value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
        } else {
            $this->value = (string) $value;
        }

        return $this;
    }

    /**
     * Return the default config value converted to its configured data type.
     *
     * @return mixed
     */
    public function getDefaultValue()
    {
        if ($this->data_type === 'json' && $this->default_value) {
            return json_decode($this->default_value, true);
        }

        if ($this->data_type === 'boolean' && $this->default_value) {
            return filter_var($this->default_value, FILTER_VALIDATE_BOOLEAN);
        }

        if ($this->data_type === 'integer') {
            return (int) $this->default_value;
        }

        if ($this->data_type === 'decimal') {
            return (float) $this->default_value;
        }

        return $this->default_value;
    }

    /**
     * Reset the current config value back to its default and save the model.
     *
     * @return bool
     */
    public function resetToDefault()
    {
        $this->value = $this->default_value;
        return $this->save();
    }

    /**
     * Determine whether the current value differs from the default value.
     *
     * @return bool
     */
    public function isModified(): bool
    {
        return $this->value !== $this->default_value;
    }

    // Static helpers
    /**
     * Get a config value by its key, returning a fallback if not found.
     *
     * @param  string  $key
     * @param  mixed|null  $default
     * @return mixed
     */
    public static function getByKey(string $key, $default = null)
    {
        $config = self::where('key', $key)->first();
        return $config ? $config->getValue() : $default;
    }

    /**
     * Set a config value by its key and save the record.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    public static function setByKey(string $key, $value): bool
    {
        $config = self::where('key', $key)->firstOrFail();
        return $config->setValue($value)->save();
    }

    /**
     * Retrieve all configs as a key => value array, with values cast to their types.
     *
     * @return array
     */
    public static function getAllConfigs(): array
    {
        return self::all()->mapWithKeys(function ($config) {
            return [$config->key => $config->getValue()];
        })->toArray();
    }

    /**
     * Retrieve all public configs as a key => value array.
     *
     * @return array
     */
    public static function getPublicConfigs(): array
    {
        return self::public()->get()->mapWithKeys(function ($config) {
            return [$config->key => $config->getValue()];
        })->toArray();
    }

    /**
     * --------------------------------------------------------------------------------------
     * TABLE FIELD DESCRIPTIONS
     * --------------------------------------------------------------------------------------
     * Field                    |   Purpose
     * key                      |   Unique identifier for the config (e.g., bg_color_primary)
     * value                    |   Current value (what's actually used)
     * default_value            |   Reset value if needed
     * data_type                |   Validates the type of value stored
     * is_public                |   true = editable by end users, false = admin only
     * is_locked                |   Prevent accidental modifications
     * created_by/updated_by    |   Track who made changes
     * timestamps               |   Audit trail
     */
}
