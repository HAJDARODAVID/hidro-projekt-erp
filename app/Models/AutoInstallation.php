<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutoInstallation extends Model
{
    protected $fillable = [
        'file_name',
        'installation_type',
        'data',
        'success',
        'error',
        'installed_at',
    ];

    protected $casts = [
        'success' => 'boolean',
        'installed_at' => 'datetime',
    ];
}
