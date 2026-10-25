<?php

namespace App\Support;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * HttpOnly cookie that carries the API token for browser clients.
 */
class AuthCookie
{
    public static function name(): string
    {
        return config('aurex.auth_cookie.name');
    }

    /**
     * Whether the client asked for cookie-based auth instead of a bearer token.
     */
    public static function requested(Request $request): bool
    {
        return strtolower((string) $request->header('X-Aurex-Auth')) === 'cookie';
    }

    /**
     * Browsers send "Sec-Fetch-Site: cross-site" when the page and the API are
     * on different sites (e.g. a localhost build talking to production). A
     * SameSite cookie is not sent back in that case, so such clients also get
     * the token in the body and keep it in memory only.
     */
    public static function isCrossSite(Request $request): bool
    {
        return strtolower((string) $request->header('Sec-Fetch-Site')) === 'cross-site';
    }

    public static function lifetimeMinutes(): int
    {
        return max(1, config('aurex.auth_cookie.lifetime_days')) * 24 * 60;
    }

    public static function make(Request $request, string $plainToken): Cookie
    {
        return cookie(
            self::name(),
            $plainToken,
            self::lifetimeMinutes(),
            '/api',
            config('aurex.auth_cookie.domain'),
            $request->isSecure(),
            true,
            false,
            config('aurex.auth_cookie.same_site'),
        );
    }

    public static function forget(Request $request): Cookie
    {
        return cookie(
            self::name(),
            '',
            -2628000,
            '/api',
            config('aurex.auth_cookie.domain'),
            $request->isSecure(),
            true,
            false,
            config('aurex.auth_cookie.same_site'),
        );
    }
}
