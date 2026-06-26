<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->addCorsHeaders(response('', 204), $request);
        }

        return $this->addCorsHeaders($next($request), $request);
    }

    private function addCorsHeaders(Response $response, Request $request): Response
    {
        $origin = $request->headers->get('Origin');

        if ($origin !== null && $this->isAllowedOrigin($origin)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Vary', 'Origin');
        }

        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Max-Age', '86400');

        return $response;
    }

    private function isAllowedOrigin(string $origin): bool
    {
        return (bool) preg_match(
            '/^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$|^https?:\/\/(app|api)\.aurex-performance\.com$/',
            $origin
        );
    }
}
