<?php

namespace App\Services;

use App\Mail\PasswordResetCodeMail;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class PasswordResetService
{
    public function send(string $email): void
    {
        DB::transaction(function () use ($email): void {
            $user = $this->findUser($email);
            if (! $user || $user->status !== 'Active') {
                return;
            }
            $previous = DB::table('password_reset_codes')->where('user_id', $user->id)->first();
            if ($previous && Carbon::parse($previous->sent_at)->addMinute()->isFuture()) {
                return;
            }
            $key = 'password-reset:'.$user->id;
            if (RateLimiter::tooManyAttempts($key, 5)) {
                return;
            }
            do {
                $code = (string) random_int(100000, 999999);
            } while ($previous?->code_hash && Hash::check($code, $previous->code_hash));

            DB::table('password_reset_codes')->updateOrInsert(['user_id' => $user->id], [
                'email' => $user->email,
                'password_fingerprint' => hash('sha256', $user->password),
                'code_hash' => Hash::make($code),
                'reset_token_hash' => null,
                'attempts' => 0,
                'expires_at' => now()->addMinutes(10),
                'sent_at' => now(),
            ]);
            // If delivery fails, retain the previous code/grant and allow another attempt.
            Mail::to($user->email)->send(new PasswordResetCodeMail($user->name, $code));
            RateLimiter::hit($key, 3600);
        });
    }

    public function verify(string $email, string $code): ?string
    {
        return DB::transaction(function () use ($email, $code): ?string {
            $user = $this->findUser($email);
            if (! $user) {
                return null;
            }
            $query = DB::table('password_reset_codes')->where('user_id', $user->id);
            $record = $query->first();
            if (! $this->validRecord($user, $record) || ! $record->code_hash || $record->attempts >= 5) {
                return null;
            }
            if (! Hash::check($code, $record->code_hash)) {
                $query->increment('attempts');
                return null;
            }

            $token = Str::random(64);
            $query->update([
                'code_hash' => null,
                'reset_token_hash' => hash('sha256', $token),
                'expires_at' => now()->addMinutes(10),
            ]);

            return $token;
        });
    }

    public function reset(string $email, string $token, string $password): bool
    {
        return DB::transaction(function () use ($email, $token, $password): bool {
            $user = $this->findUser($email);
            if (! $user) {
                return false;
            }
            $query = DB::table('password_reset_codes')->where('user_id', $user->id);
            $record = $query->first();
            if (! $this->validRecord($user, $record) || ! $record->reset_token_hash
                || ! hash_equals($record->reset_token_hash, hash('sha256', $token))) {
                return false;
            }

            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
                'force_password_change' => false,
            ])->save();
            $query->delete();
            ApiToken::where('user_id', $user->id)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();

            return true;
        });
    }

    private function findUser(string $email): ?User
    {
        return User::query()->whereRaw('LOWER(email) = ?', [strtolower(trim($email))])->lockForUpdate()->first();
    }

    private function validRecord(User $user, ?object $record): bool
    {
        return $record && $user->status === 'Active' && $record->email === $user->email
            && Carbon::parse($record->expires_at)->isFuture()
            && hash_equals($record->password_fingerprint, hash('sha256', $user->password));
    }
}
