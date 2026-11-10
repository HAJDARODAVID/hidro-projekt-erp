<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\Application\Acl\AclService;
use Symfony\Component\HttpFoundation\Response;

/**
 * New ACL route guard (alias: acl).
 *
 *   ->middleware('acl')                    the permission bound to this route in /app/acl decides
 *                                          (routes without a bound permission stay open)
 *   ->middleware('acl:payroll.view')       the user needs this permission
 *   ->middleware('acl:a.view,b.view')      the user needs at least one of them
 */
class AclAccess
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $acl = AclService::current();

        $allowed = empty($permissions)
            ? $acl->canRoute($request->route()?->getName())
            : $acl->canAny(...$permissions);

        if (!$allowed) {
            if ($request->expectsJson()) abort(403, translator('You do not have access to this page!'));
            return redirect(route('home'))->withHeaders(['x-message' => 'No access to: ' . ($request->route()?->getName() ?? $request->path())]);
        }

        return $next($request);
    }
}
