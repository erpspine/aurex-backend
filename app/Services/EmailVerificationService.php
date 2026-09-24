<?php

namespace App\Services;

use App\Mail\EmailVerificationMail;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EmailVerificationService
{
    // Return the resend cooldown without replacing an unexpired, recently sent code.
    public function send(User $user): int
    {
        return DB::transaction(function () use ($user): int {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            if ($user->email_verified_at) {
                return 0;
            }

            $previous = DB::table('email_verification_codes')->where('user_id', $user->id)->first();
            if ($previous && $previous->email === $user->email) {
                $remaining = (int) ceil(now()->diffInSeconds(Carbon::parse($previous->sent_at)->addMinute(), false));
                if ($remaining > 0) {
                    return $remaining;
                }
            }

            $key = 'email-verification:'.$user->id;
            if (RateLimiter::tooManyAttempts($key, 5)) {
                throw new HttpException(429, 'Too many verification emails. Please try again in an hour.');
            }

            do {
                $code = (string) random_int(100000, 999999);
            } while ($previous && Hash::check($code, $previous->code_hash));
            DB::table('email_verification_codes')->updateOrInsert(['user_id' => $user->id], [
                'email' => $user->email,
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(10),
                'sent_at' => now(),
            ]);

            // A delivery error rolls back the new code, leaving any previous code usable.
            Mail::to($user->email)->send(new EmailVerificationMail($user->name, $code));
            RateLimiter::hit($key, 3600);

            return 60;
        });
    }

    public function verify(string $email, string $code): bool
    {
        return DB::transaction(function () use ($email, $code): bool {
            $user = User::query()->whereRaw('LOWER(email) = ?', [strtolower($email)])->lockForUpdate()->first();
            if (! $user || $user->email_verified_at) {
                return false;
            }

            $query = DB::table('email_verification_codes')->where('user_id', $user->id);
            $record = $query->first();
            if (! $record || $record->email !== $user->email || Carbon::parse($record->expires_at)->isPast() || $record->attempts >= 5) {
                return false;
            }

            if (! Hash::check($code, $record->code_hash)) {
                $query->increment('attempts');
                return false;
            }

            $user->forceFill(['email_verified_at' => now()])->save();
            $query->delete();

            return true;
        });
    }
}
