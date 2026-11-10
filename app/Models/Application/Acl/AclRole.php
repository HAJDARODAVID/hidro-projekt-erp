<?php

namespace App\Models\Application\Acl;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AclRole extends Model
{
    protected $table = 'acl_roles';
    protected $fillable = ['key', 'name', 'description', 'active'];

    public $incrementing = FALSE;
    protected $keyType = 'string';

    protected $casts = [
        'active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (AclRole $role) {
            if (empty($role->id)) $role->id = Str::uuid()->toString();
        });
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(AclPermission::class, 'acl_role_permission', 'role_id', 'permission_id');
    }
}
