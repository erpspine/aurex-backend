<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiTokenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        return response()->json([
            'tokens' => ApiToken::query()
                ->with('user:id,name,email,role,status')
                ->latest()
                ->get()
                ->map(fn (ApiToken $token): array => $this->serializeToken($token)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->ensureAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'token_type' => ['required', 'in:dashboard,service'],
            'scopes' => ['nullable', 'array'],
            'scopes.*' => ['string', 'in:sync,turnstile'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $scopes = $data['scopes'] ?? null;

        if (($data['token_type'] ?? 'dashboard') === 'service' && $scopes === null) {
            $scopes = ['sync'];
        }

        $plainToken = Str::random(80);

        $token = ApiToken::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'token_type' => $data['token_type'],
            'scopes' => $scopes,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return response()->json([
            'message' => 'API token created successfully.',
            'token' => $plainToken,
            'api_token' => $this->serializeToken($token),
        ], 201);
    }

    public function destroy(Request $request, ApiToken $apiToken): JsonResponse
    {
        $this->ensureAdmin($request);

        $apiToken->update([
            'revoked_at' => now(),
        ]);

        return response()->json([
            'message' => 'API token revoked successfully.',
        ]);
    }

    private function ensureAdmin(Request $request)
    {
        $user = $request->user();

        $role = strtolower(trim((string) ($user->role ?? '')));
        $allowedRoles = ['admin', 'super admin'];

        abort_unless(
            $user && in_array($role, $allowedRoles, true),
            403,
            'Only Admin and Super Admin users can manage API tokens.',
        );

        return $user;
    }

    private function serializeToken(ApiToken $token): array
    {
        return [
            'id' => $token->id,
            'user_id' => $token->user_id,
            'user' => $token->user,
            'name' => $token->name,
            'token_type' => $token->token_type ?? 'dashboard',
            'scopes' => $token->scopes ?? [],
            'last_used_at' => $token->last_used_at?->toISOString(),
            'expires_at' => $token->expires_at?->toISOString(),
            'revoked_at' => $token->revoked_at?->toISOString(),
            'created_at' => $token->created_at?->toISOString(),
            'updated_at' => $token->updated_at?->toISOString(),
        ];
    }
}