<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CooperatorsModel extends Model
{
    use HasFactory;

    const COOPERATORS_STATUS_ACTIVE = 1;
    const COOPERATORS_STATUS_INACTIVE = 0;
    const COOPERATORS_STATUS_DELETED = -1;

    const COOPERATORS_STATUS = array(
        self::COOPERATORS_STATUS_ACTIVE => 'Aktivni',
        self::COOPERATORS_STATUS_INACTIVE => 'Ne aktivni',
        self::COOPERATORS_STATUS_DELETED => 'Obrisani',
    );

    protected $table = 'cooperators';

    protected $fillable = [
        'name',
        'status',
    ];

    public function getAllWorkers(): HasMany
    {
        return $this->hasMany(CooperatorWorkersModel::class, 'cooperator_id', 'id');
    }

    public function getFullNameAttribute()
    {
        return $this->attributes['firstName'] . ' ' . $this->attributes['lastName'];
    }
}
