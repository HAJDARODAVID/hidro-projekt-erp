<?php

namespace App\Models\Application;

use App\Services\Application\CompanyContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A menu built with the menu builder. Rendered with <x-ui.app-menu menu="key" />.
 */
class AppMenu extends Model
{
    /** Key of the sidebar menu */
    public const KEY_MAIN = 'main';

    protected $table = 'app_menu';

    protected $fillable = [
        'key',
        'name',
        'company_id',
        'active',
    ];

    protected $casts = [
        'active'     => 'boolean',
        'company_id' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(AppMenuItem::class, 'menu_id')->orderBy('position');
    }

    public function scopeOfCompany($query, ?int $companyId)
    {
        return CompanyContext::scope($query, $companyId);
    }
}
