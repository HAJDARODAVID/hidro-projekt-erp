<?php

namespace App\Models\Jobs;

use App\Models\WorkDiary\WorkDiary;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConstructionSite extends Model
{
    use HasFactory;

    protected $table = 'construction_sites';

    protected $fillable = [
        'name',
        'street',
        'town',
        'start_date',
        'end_date',
        'job_description',
        'status',
    ];

    /**
     * Return a array of all the workday report ID's.
     * 
     * @return array 
     */
    public function getAllWdrID(): array
    {
        $wdr = WorkDiary::where('construction_site_id', $this->attributes['id'])->pluck('id')->toArray();
        return $wdr;
    }
}
