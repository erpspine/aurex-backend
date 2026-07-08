<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if (! $plainToken) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token = ApiToken::query()
            ->where('token_hash', hash('sha256', $plainToken))
            ->with('user')
            ->first();

        if (! $token || ! $token->user || $token->user->status !== 'Active') {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($token->revoked_at) {
            return response()->json(['message' => 'Token revoked.'], 401);
        }

        if ($token->expires_at && $token->expires_at->isPast()) {
            $token->delete();

            return response()->json(['message' => 'Session expired.'], 401);
        }

        if (($token->token_type ?? 'dashboard') === 'service' && ! $this->allowsServiceRoute($request, $token->scopes ?? [])) {
            return response()->json(['message' => 'Token not allowed for this endpoint.'], 403);
        }

        $token->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('api_token', $token);
        $request->setUserResolver(fn () => $token->user);

        return $next($request);
    }

    /**
     * @param array<int, string> $scopes
     */
    private function allowsServiceRoute(Request $request, array $scopes): bool
    {
        $scopes = array_values(array_unique(array_filter(array_map(
            static fn (mixed $scope): string => strtolower(trim((string) $scope)),
            $scopes,
        ))));

        if ($scopes === []) {
            return false;
        }

        $path = trim($request->path(), '/');

        if (str_starts_with($path, 'api/sync/')) {
            return in_array('sync', $scopes, true);
        }

        if (str_starts_with($path, 'api/turnstile/')) {
            return in_array('turnstile', $scopes, true);
        }

        return false;
    }
}
