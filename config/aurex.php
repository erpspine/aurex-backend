<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Web app session cookie
    |--------------------------------------------------------------------------
    |
    | Browser clients (the Flutter web app) log in with the header
    | "X-Aurex-Auth: cookie". Their API token is then kept in an HttpOnly
    | cookie that page scripts cannot read, instead of being returned in the
    | JSON body. Native apps and the dashboard keep using bearer tokens.
    |
    */

    'auth_cookie' => [
        'name' => env('AUREX_AUTH_COOKIE', 'aurex_token'),
        'lifetime_days' => (int) env('AUREX_AUTH_COOKIE_DAYS', 30),
        // "lax" works when the app and API share a site, e.g.
        // app.aurex-performance.com and api.aurex-performance.com.
        'same_site' => env('AUREX_AUTH_COOKIE_SAME_SITE', 'lax'),
        'domain' => env('AUREX_AUTH_COOKIE_DOMAIN'),
    ],

];
