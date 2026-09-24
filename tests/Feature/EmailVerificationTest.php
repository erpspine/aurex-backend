<?php

namespace Tests\Feature;

use App\Mail\EmailVerificationMail;
use App\Models\ApiToken;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function registration(array $overrides = []): array
    {
        return array_merge([
            'name' => 'New Member', 'email' => 'member@example.test',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ], $overrides);
    }

    private function latestCode(): string
    {
        return Mail::sent(EmailVerificationMail::class)->last()->code;
    }

    public function test_signup_verification_and_login_flow(): void
    {
        Mail::fake();
        $this->postJson('/api/register', $this->registration(['role' => 'Administrator', 'email_verified_at' => now()]))
            ->assertCreated()->assertJsonPath('verification_required', true)->assertJsonMissingPath('token');
        $user = User::firstOrFail();
        $this->assertSame('Member', $user->role);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(Hash::check('Password123', $user->password));
        $this->assertSame('Pending', Member::firstOrFail()->membership_status);
        Mail::assertSent(EmailVerificationMail::class, fn ($mail) => $mail->hasTo('member@example.test'));
        $code = $this->latestCode();
        $record = DB::table('email_verification_codes')->first();
        $this->assertNotSame($code, $record->code_hash);
        $this->assertTrue(Hash::check($code, $record->code_hash));
        $this->postJson('/api/login', $this->registration())->assertForbidden()
            ->assertJsonPath('verification_required', true)->assertJsonMissingPath('token');
        $this->assertDatabaseCount('api_tokens', 0);
        Mail::assertSentCount(1);
        $this->postJson('/api/email/verify', ['email' => $user->email, 'code' => $code])->assertOk();
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseCount('email_verification_codes', 0);
        $this->postJson('/api/email/verify', ['email' => $user->email, 'code' => $code])->assertUnprocessable();
        $login = $this->postJson('/api/login', $this->registration())->assertOk();
        $this->getJson('/api/me', ['Authorization' => 'Bearer '.$login->json('token')])->assertOk();
    }

    public function test_wrong_codes_lock_after_five_attempts_and_resend_replaces_code(): void
    {
        Mail::fake();
        $this->postJson('/api/register', $this->registration())->assertCreated();
        $oldCode = $this->latestCode();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/email/verify', ['email' => 'member@example.test', 'code' => '000000'])->assertUnprocessable();
        }
        $this->assertSame(5, DB::table('email_verification_codes')->value('attempts'));
        $this->postJson('/api/email/verify', ['email' => 'member@example.test', 'code' => $oldCode])->assertUnprocessable();
        $this->travel(61)->seconds();
        $this->postJson('/api/email/resend', ['email' => 'member@example.test'])->assertOk();
        $newCode = $this->latestCode();
        $this->assertNotSame($oldCode, $newCode);
        $this->postJson('/api/email/verify', ['email' => 'member@example.test', 'code' => $oldCode])->assertUnprocessable();
        $this->postJson('/api/email/verify', ['email' => 'member@example.test', 'code' => $newCode])->assertOk();
    }

    public function test_expired_code_is_rejected(): void
    {
        Mail::fake();
        $this->postJson('/api/register', $this->registration())->assertCreated();
        $code = $this->latestCode();
        $this->travel(11)->minutes();
        $this->postJson('/api/email/verify', ['email' => 'member@example.test', 'code' => $code])->assertUnprocessable();
        $this->assertNull(User::firstOrFail()->email_verified_at);
    }

    public function test_resend_cooldown_and_hourly_limit(): void
    {
        Mail::fake();
        $this->postJson('/api/register', $this->registration())->assertCreated();
        $this->postJson('/api/email/resend', ['email' => 'member@example.test'])->assertOk();
        Mail::assertSentCount(1);
        for ($send = 0; $send < 4; $send++) {
            $this->travel(61)->seconds();
            $this->postJson('/api/email/resend', ['email' => 'member@example.test'])->assertOk();
        }
        $this->travel(61)->seconds();
        $this->postJson('/api/email/resend', ['email' => 'member@example.test'])->assertStatus(429);
        Mail::assertSentCount(5);
    }

    public function test_delivery_failure_is_recoverable_without_duplicate_registration(): void
    {
        Mail::shouldReceive('getDefaultDriver')->andReturn('array');
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new TransportException('Mail unavailable'));
        $this->postJson('/api/register', $this->registration())->assertStatus(503)
            ->assertJsonPath('verification_required', true);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('email_verification_codes', 0);
        Mail::fake();
        $this->postJson('/api/email/resend', ['email' => 'member@example.test'])->assertOk();
        Mail::assertSentCount(1);
    }

    public function test_duplicate_registration_does_not_change_account_and_password_is_confirmed(): void
    {
        Mail::fake();
        $this->postJson('/api/register', $this->registration(['password_confirmation' => 'different']))->assertUnprocessable();
        $this->assertDatabaseCount('users', 0);
        $this->postJson('/api/register', $this->registration())->assertCreated();
        $this->postJson('/api/register', $this->registration(['email' => 'MEMBER@example.test', 'name' => 'Different']))->assertUnprocessable();
        $this->assertSame('New Member', User::firstOrFail()->name);
        Mail::assertSentCount(1);
    }

    public function test_existing_unverified_accounts_are_gated_and_wrong_password_sends_no_email(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create(['user_type' => 'Member', 'role' => 'Member', 'status' => 'Active']);
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong'])->assertUnprocessable();
        Mail::assertNothingSent();
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
            ->assertForbidden()->assertJsonPath('verification_required', true);
        Mail::assertSentCount(1);
        ApiToken::create(['user_id' => $user->id, 'name' => 'old-session', 'token_hash' => hash('sha256', 'old-token')]);
        $this->getJson('/api/me', ['Authorization' => 'Bearer old-token'])->assertForbidden();
    }

    public function test_changing_email_requires_verification_again_and_old_code_cannot_verify_new_address(): void
    {
        Mail::fake();
        $this->postJson('/api/register', $this->registration())->assertCreated();
        $oldCode = $this->latestCode();
        $user = User::firstOrFail();
        $user->update(['email' => 'changed@example.test']);
        $this->postJson('/api/email/verify', ['email' => $user->email, 'code' => $oldCode])->assertUnprocessable();
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->update(['email' => 'another@example.test']);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_mail_reuses_aurex_design_and_contains_code(): void
    {
        $html = (new EmailVerificationMail('Aurex Member', '123456'))->render();
        $this->assertStringContainsString('123456', $html);
        $this->assertStringContainsString('#C8A13A', $html);
        $this->assertStringContainsString('PERFORMANCE ARENA', $html);
        $this->assertStringContainsString('10 minutes', $html);
    }
}
