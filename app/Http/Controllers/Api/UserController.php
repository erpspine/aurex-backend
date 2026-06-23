<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\UserCredentialsMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::query()
            ->select([
                'id',
                'name',
                'email',
                'phone',
                'user_type',
                'role',
                'status',
                'two_factor_enabled',
                'force_password_change',
                'created_at',
                'updated_at',
            ])
            ->latest()
            ->get();

        return response()->json([
            'users' => $users,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'user_type' => ['required', 'string', 'max:100'],
            'role' => ['required', 'string', 'max:100'],
            'status' => ['required', Rule::in(['Active', 'Inactive', 'Suspended'])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'two_factor_enabled' => ['required', 'boolean'],
            'force_password_change' => ['required', 'boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'user_type' => $validated['user_type'],
            'role' => $validated['role'],
            'status' => $validated['status'],
            'password' => Hash::make($validated['password']),
            'two_factor_enabled' => $validated['two_factor_enabled'],
            'force_password_change' => $validated['force_password_change'],
        ]);

        Mail::to($user->email)->send(new UserCredentialsMail(
            user: $user,
            plainPassword: $validated['password'],
            systemUrl: (string) config('app.frontend_url'),
        ));

        return response()->json([
            'message' => 'User created successfully and credentials email sent.',
            'user' => $user,
        ], 201);
    }
}
