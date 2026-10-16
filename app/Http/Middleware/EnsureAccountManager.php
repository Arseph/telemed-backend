<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAccountManager
{
    /**
     * Allow only users whose level is in config('admin.manager_roles').
     * Must run after auth:sanctum.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !in_array($user->level, config('admin.manager_roles'), true)) {
            abort(403, 'You are not allowed to manage accounts.');
        }

        return $next($request);
    }
}
