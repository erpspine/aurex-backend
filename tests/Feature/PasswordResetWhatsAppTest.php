<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.access_token' => 'test-token',
            'services.whatsapp.phone_number_id' => '1024965370698666',
        ]);
        Mail::fake();

        $this->postJson('/api/register', [
            'name' => 'Reset Member', 'email' => 'reset@example.test',
            'phone' => '0784243530', 'password' => 'Password123', 'password_confirmation' => 'Password123',
        ])->assertCreated();
        User::where('email', 'reset@example.test')->update(['phone' => '+255 784 243 530', 'status' => 'Active']);
    }

    public function test_reset_code_is_sent_with_the_verification_template(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);

        $this->postJson('/api/password/forgot', ['email' => 'reset@example.test'])->assertOk();

        Http::assertSent(function (Request $request): bool {
            $code = $request['template']['components'][0]['parameters'][0]['text'];

            return $request->url() === 'https://graph.facebook.com/v22.0/1024965370698666/messages'
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && $request['to'] === '255784243530'
                && $request['template']['name'] === 'verification_code'
                && $request['template']['language']['code'] === 'en'
                && preg_match('/^\d{6}$/', $code) === 1
                && $request['template']['components'][1]['parameters'][0]['text'] === $code;
        });
    }

    public function test_whatsapp_is_skipped_when_not_configured(): void
    {
        config(['services.whatsapp.access_token' => null]);
        Http::fake();

        $this->postJson('/api/password/forgot', ['email' => 'reset@example.test'])
            ->assertOk()
            ->assertJsonPath('message', fn (string $message) => str_contains($message, 'WhatsApp'));

        Http::assertNothingSent();
        Mail::assertSent(\App\Mail\PasswordResetCodeMail::class);
    }

    public function test_whatsapp_failure_does_not_block_the_email(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid token']], 401)]);

        $this->postJson('/api/password/forgot', ['email' => 'reset@example.test'])->assertOk();

        Mail::assertSent(\App\Mail\PasswordResetCodeMail::class);
    }
}
