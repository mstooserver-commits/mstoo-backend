<?php

$frontend = env('FRONTEND_URL', '');
$admin = env('ADMIN_URL', '');
$extra = env('CORS_ALLOWED_ORIGINS', '');

$origins = array_values(array_filter(array_map('trim', explode(',', implode(',', [
    $frontend,
    $admin,
    $extra,
])))));

// Local/dev fallback so Next.js can work out of the box without breaking mobile apps.
if ($origins === []) {
    $origins = ['*'];
}

$supportsCredentials = filter_var(env('CORS_SUPPORTS_CREDENTIALS', false), FILTER_VALIDATE_BOOLEAN);
if ($supportsCredentials && in_array('*', $origins, true)) {
    // Browsers reject credentials with wildcard origins.
    $supportsCredentials = false;
}

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | FRONTEND_URL / ADMIN_URL / CORS_ALLOWED_ORIGINS control allowed origins.
    | Use comma-separated values for multiple Next.js / admin origins.
    | Keep supports_credentials false while using Passport Bearer tokens.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'payment/*', 'storage/*', 'storage/app/public/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => $origins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'Origin',
        'X-Requested-With',
        'X-localization',
        'X-Localization',
        'zoneid',
        'zoneId',
        'zone-id',
        'ZoneId',
    ],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => $supportsCredentials,

];
