<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LocalAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Local admin seeding was skipped outside the local/testing environment.');

            return;
        }

        $email = (string) env('LOCAL_ADMIN_EMAIL', 'admin@aurex.local');
        $password = (string) env('LOCAL_ADMIN_PASSWORD', 'AurexLocal2026!');

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Local Administrator',
                'phone' => null,
                'user_type' => 'Admin',
                'role' => 'Administrator',
                'status' => 'Active',
                'password' => Hash::make($password),
                'two_factor_enabled' => false,
                'force_password_change' => false,
            ],
        );

        $this->command?->info("Local administrator ready: {$email}");
    }
}
