<?php

namespace App\Models\WorkDiary;

use App\Models\Jobs\ConstructionSite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\User\User;

class WorkDiary extends Model
{
    use HasFactory;

    protected $table = "working_day_record";

    protected $fillable = [
        'user_id',
        'construction_site_id',
        'car_id',
        'date',
        'work_description',
        'work_type',
    ];

    /**
     * Add the corresponding User model to the relation
     * 
     * @return HasOne
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    /**
     * Add the corresponding Construction Site model to the relation
     * 
     * @return HasOne
     */
    public function constructionSite(): HasOne
    {
        return $this->hasOne(ConstructionSite::class, 'id', 'construction_site_id');
    }
}
