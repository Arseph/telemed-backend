<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;

class RestrictToNetwork
{
    /**
     * Refuse the request unless it comes from an allowed network.
     *
     * Reads REMOTE_ADDR directly instead of $request->ip() so a spoofed
     * X-Forwarded-For header can never satisfy the check.
     */
    public function handle(Request $request, Closure $next)
    {
        $addr = $request->server('REMOTE_ADDR');

        if (!$addr || !IpUtils::checkIp($addr, config('admin.allowed_networks'))) {
            abort(403, 'Account management is only available on the office network.');
        }

        return $next($request);
    }
}
