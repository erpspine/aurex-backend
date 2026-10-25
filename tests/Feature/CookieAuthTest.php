<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CookieAuthTest extends TestCase
{
    use RefreshDatabase;

    private const WEB = ['X-Aurex-Auth' => 'cookie', 'X-Requested-With' => 'XMLHttpRequest'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->postJson('/api/register', [
            'name' => 'Web Member', 'email' => 'web@example.test',
            'phone' => '0784243530', 'password' => 'Password123', 'password_confirmation' => 'Password123',
        ])->assertCreated();
        User::where('email', 'web@example.test')->update(['email_verified_at' => now()]);
    }

    private function login(array $headers = self::WEB)
    {
        return $this->postJson('/api/login', [
            'email' => 'web@example.test', 'password' => 'Password123',
        ], $headers)->assertOk();
    }

    public function test_cookie_login_hides_the_token_and_authenticates_requests(): void
    {
        $response = $this->login()->assertJsonMissingPath('token');

        $cookie = $response->getCookie('aurex_token', false);
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
        $this->assertSame('/api', $cookie->getPath());
        $this->assertNotNull(ApiToken::firstOrFail()->expires_at);

        $this->withCredentials()->withUnencryptedCookie('aurex_token', $cookie->getValue())
            ->getJson('/api/me', self::WEB)
            ->assertOk()
            ->assertJsonPath('user.email', 'web@example.test');
    }

    public function test_cookie_requests_need_the_csrf_header(): void
    {
        $cookie = $this->login()->getCookie('aurex_token', false);

        $this->withCredentials()->withUnencryptedCookie('aurex_token', $cookie->getValue())
            ->getJson('/api/me')
            ->assertForbidden();
    }

    public function test_cross_site_clients_also_receive_the_token_for_memory_use(): void
    {
        $this->login([...self::WEB, 'Sec-Fetch-Site' => 'cross-site'])
            ->assertJsonStructure(['token']);
    }

    public function test_bearer_login_is_unchanged(): void
    {
        $response = $this->login([])->assertJsonStructure(['token']);
        $this->assertNull($response->getCookie('aurex_token', false));
        $this->assertNull(ApiToken::firstOrFail()->expires_at);
    }

    public function test_logout_revokes_the_cookie_token(): void
    {
        $cookie = $this->login()->getCookie('aurex_token', false);

        $logout = $this->withCredentials()->withUnencryptedCookie('aurex_token', $cookie->getValue())
            ->postJson('/api/logout', [], self::WEB)
            ->assertOk();

        $this->assertTrue($logout->getCookie('aurex_token', false)->isCleared());
        $this->assertDatabaseCount('api_tokens', 0);
    }
}
