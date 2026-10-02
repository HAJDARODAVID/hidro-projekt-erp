<?php

namespace App\Models\Application;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppModuleRoute extends Model
{
    use HasFactory;
    protected $table = 'app_module_routes';
    protected $fillable = ['title', 'route_name', 'method', 'active', 'resource_id', 'module_id', 'position', 'divider'];

    public $incrementing = FALSE;

    protected static function booted(): void
    {
        static::creating(function (AppModuleRoute $appModuleRoute) {
            // Keep a preset ID (routes synced from another environment keep their ID)
            if (empty($appModuleRoute->id)) $appModuleRoute->id = Str::uuid()->toString();
        });
    }

    protected $casts = [
        'active' => 'boolean',
    ];
}
