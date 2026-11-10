<?php

namespace App\Models\Application\Acl;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use App\Models\Application\AppModule;
use App\Models\Application\AppModuleRoute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AclPermission extends Model
{
    protected $table = 'acl_permissions';
    protected $fillable = ['key', 'name', 'description', 'module_id', 'route_id'];

    public $incrementing = FALSE;
    protected $keyType = 'string';

    protected static function booted(): void
    {
        static::creating(function (AclPermission $permission) {
            if (empty($permission->id)) $permission->id = Str::uuid()->toString();
        });
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(AclRole::class, 'acl_role_permission', 'permission_id', 'role_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(AppModule::class, 'module_id', 'id');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(AppModuleRoute::class, 'route_id', 'id');
    }
}
