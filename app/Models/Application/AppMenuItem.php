<?php

namespace App\Models\Application;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One entry of an AppMenu. Items form a tree through parent_id (only groups have children).
 */
class AppMenuItem extends Model
{
    public const TYPE_LINK = 'link';
    public const TYPE_GROUP = 'group';
    public const TYPE_HEADER = 'header';
    public const TYPE_DIVIDER = 'divider';

    public const TYPES = [
        self::TYPE_LINK    => ['label' => 'Link',    'icon' => 'link-45deg'],
        self::TYPE_GROUP   => ['label' => 'Group',   'icon' => 'folder2'],
        self::TYPE_HEADER  => ['label' => 'Header',  'icon' => 'type-h3'],
        self::TYPE_DIVIDER => ['label' => 'Divider', 'icon' => 'dash-lg'],
    ];

    protected $table = 'app_menu_item';

    protected $fillable = [
        'menu_id',
        'parent_id',
        'type',
        'title',
        'icon',
        'route_name',
        'url',
        'new_tab',
        'permission',
        'position',
        'active',
    ];

    protected $casts = [
        'new_tab'  => 'boolean',
        'active'   => 'boolean',
        'position'  => 'integer',
        'menu_id'   => 'integer',
        'parent_id' => 'integer',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(AppMenu::class, 'menu_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }

    public function isGroup(): bool
    {
        return $this->type === self::TYPE_GROUP;
    }
}
