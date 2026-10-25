<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberRouteAccessTest extends TestCase
{
    use RefreshDatabase;

    private function memberHeaders(): array
    {
        $this->postJson('/api/register', [
            'name' => 'App Member', 'email' => 'app-member@example.test',
            'phone' => '0784243530', 'password' => 'Password123', 'password_confirmation' => 'Password123',
        ])->assertCreated();

        User::where('email', 'app-member@example.test')->update(['email_verified_at' => now()]);
        $token = $this->postJson('/api/login', [
            'email' => 'app-member@example.test', 'password' => 'Password123',
        ])->assertOk()->json('token');

        return ['Authorization' => 'Bearer '.$token];
    }

    public function test_member_can_read_the_mobile_catalogue(): void
    {
        $headers = $this->memberHeaders();

        foreach (['/api/workouts', '/api/exercises', '/api/equipment', '/api/workout-levels', '/api/app-banners', '/api/diet-plans'] as $path) {
            $this->getJson($path, $headers)->assertOk();
        }
    }

    public function test_member_cannot_write_or_read_admin_endpoints(): void
    {
        $headers = $this->memberHeaders();

        $this->postJson('/api/exercises', [], $headers)->assertForbidden();
        $this->postJson('/api/diet-plans', [], $headers)->assertForbidden();
        $this->getJson('/api/members', $headers)->assertForbidden();
        $this->getJson('/api/attendance', $headers)->assertForbidden();
        $this->getJson('/api/mobile-app', $headers)->assertForbidden();
    }
}
