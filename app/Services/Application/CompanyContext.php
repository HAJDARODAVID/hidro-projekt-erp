<?php

namespace App\Services\Application;

use App\Models\User;

/**
 * The one place that knows which company a user belongs to.
 *
 * There is no company logic yet: users have no company_id, so everybody is in the NULL "company".
 * Tables that are company scoped (app_dashboard, app_notification) already store company_id,
 * so only this class has to change once companies exist.
 */
class CompanyContext
{
    public static function idOf(?User $user): ?int
    {
        $companyId = $user?->getAttribute('company_id');

        return $companyId === NULL ? NULL : (int) $companyId;
    }

    /**
     * NULL-safe "company_id = ?" condition (NULL matches NULL).
     *
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     */
    public static function scope($query, ?int $companyId, string $column = 'company_id')
    {
        return $companyId === NULL ? $query->whereNull($column) : $query->where($column, $companyId);
    }
}
