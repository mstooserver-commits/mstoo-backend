<?php

namespace App\Support;

use Illuminate\Http\Request;

class UrlScheme
{
    public static function forRequest(Request $request): string
    {
        return self::isHttps($request) ? 'https' : 'http';
    }

    public static function isHttps(Request $request): bool
    {
        if ($request->isSecure()) {
            return true;
        }

        $forwarded = strtolower((string) $request->headers->get('X-Forwarded-Proto', ''));
        $forwarded = trim(explode(',', $forwarded)[0]);

        return $forwarded === 'https';
    }

    public static function isHttpRuntime(): bool
    {
        return !in_array(PHP_SAPI, ['cli', 'phpdbg'], true);
    }
}
