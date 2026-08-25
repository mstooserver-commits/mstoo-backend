<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TrustProxies
{
    /**
     * Laravel 8 does not ship Illuminate\Http\Middleware\TrustProxies.
     * Trust proxy headers so scheme / host detection works behind nginx.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $headers = Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO;

        if (defined(Request::class . '::HEADER_X_FORWARDED_AWS_ELB')) {
            $headers |= Request::HEADER_X_FORWARDED_AWS_ELB;
        }

        $request->setTrustedProxies(['0.0.0.0/0', '::/0'], $headers);

        return $next($request);
    }
}
