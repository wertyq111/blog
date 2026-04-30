<?php

use Laravel\Sanctum\Sanctum;

$statefulLocalHost = env('SANCTUM_LOCAL_HOST', 'localhost');
$statefulLoopbackHost = env('SANCTUM_LOOPBACK_HOST', '127.0.0.1');
$statefulIpv6LoopbackHost = env('SANCTUM_IPV6_LOOPBACK_HOST', '::1');
$statefulFrontendPort = env('SANCTUM_FRONTEND_PORT');
$statefulBackendPort = env('SANCTUM_BACKEND_PORT');

$statefulFallbackDomains = array_filter([
    $statefulLocalHost,
    $statefulFrontendPort ? $statefulLocalHost . ':' . $statefulFrontendPort : null,
    $statefulLoopbackHost,
    $statefulBackendPort ? $statefulLoopbackHost . ':' . $statefulBackendPort : null,
    $statefulIpv6LoopbackHost,
    Sanctum::currentApplicationUrlWithPort(),
]);

$statefulDomains = env('SANCTUM_STATEFUL_DOMAINS') ?: implode(',', $statefulFallbackDomains);

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Requests from the following domains / hosts will receive stateful API
    | authentication cookies. Typically, these should include your local
    | and production domains which access your API via a frontend SPA.
    |
    */

    'stateful' => array_values(array_filter(array_map('trim', explode(',', $statefulDomains)))),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    |
    | This array contains the authentication guards that will be checked when
    | Sanctum is trying to authenticate a request. If none of these guards
    | are able to authenticate the request, Sanctum will use the bearer
    | token that's present on an incoming request for authentication.
    |
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | This value controls the number of minutes until an issued token will be
    | considered expired. If this value is null, personal access tokens do
    | not expire. This won't tweak the lifetime of first-party sessions.
    |
    */

    'expiration' => null,

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    |
    | When authenticating your first-party SPA with Sanctum you may need to
    | customize some of the middleware Sanctum uses while processing the
    | request. You may change the middleware listed below as required.
    |
    */

    'middleware' => [
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
    ],

];
