<?php

namespace App\Models\User;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\UserRole;
use App\Models\WorkerModel;
use App\Models\SpecialPrivilege;
use Laravel\Sanctum\HasApiTokens;
use App\Models\CooperatorWorkersModel;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    const USER_TYPE_MANAGER      = 1;
    const USER_TYPE_ADMIN_STAFF  = 2;
    const USER_TYPE_GROUP_LEADER = 3;
    const USER_TYPE_SUPER_ADMIN  = 4;
    const USER_TYPE_COOPERATOR   = 5;

    const USER_TYPE = array(
        self::USER_TYPE_MANAGER      => 'Manager',
        self::USER_TYPE_ADMIN_STAFF  => 'Administration staff',
        self::USER_TYPE_GROUP_LEADER => 'Group leader',
        self::USER_TYPE_SUPER_ADMIN  => 'Super admin',
        self::USER_TYPE_COOPERATOR   => 'Cooperator',
    );

    const DEFAULT_PASSWORD = 123456;

    protected $attributes = [
        'type' => self::USER_TYPE_ADMIN_STAFF,
        'active' => 1,
    ];


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'last_name',
        'email',
        'password',
        'type',
        'user_type',
        'lang',
        'worker_id',
        'coop_id',
        'active',
        'inv_update',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'inv_update' => 'bool',
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    // public function getWorkerEntry():HasOne{
    //     return $this->hasOne(WorkerModel::class, 'worker_id','id');
    // }

    public function getWorker(): HasOne
    {
        return $this->hasOne(WorkerModel::class, 'id', 'worker_id');
    }

    public function getCooperator(): HasOne
    {
        return $this->hasOne(CooperatorWorkersModel::class, 'id', 'coop_id');
    }

    public function getSpecialPrivilege(): HasMany
    {
        return $this->hasMany(SpecialPrivilege::class, 'user_id', 'id');
    }

    public function getUserRoles(): HasMany
    {
        return $this->hasMany(UserRole::class, 'user_id', 'id');
    }
}
