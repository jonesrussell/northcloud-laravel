<?php

namespace JonesRussell\NorthCloud\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $policyClass = config('northcloud.admin.policy');

        if ($policyClass) {
            $policy = app($policyClass);
            if (! $policy->viewAdmin($request->user())) {
                abort(403, 'Unauthorized. Admin access required.');
            }
        } elseif (method_exists($request->user(), 'isAdmin')) {
            if (! $request->user()->isAdmin()) {
                abort(403, 'Unauthorized. Admin access required.');
            }
        } elseif (! $request->user()?->is_admin) {
            abort(403, 'Unauthorized. Admin access required.');
        }

        return $next($request);
    }
}
