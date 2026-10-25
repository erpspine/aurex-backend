<?php

namespace Tests\Feature;

use App\Mail\EmailVerificationMail;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationPhoneTest extends TestCase
{
    use RefreshDatabase;

    private function register(array $overrides = [])
    {
        return $this->postJson('/api/register', array_merge([
            'name' => 'Phone Member', 'email' => 'phone@example.test', 'phone' => '+255 712 345 678',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ], $overrides));
    }

    public function test_phone_is_required_and_validated(): void
    {
        Mail::fake();

        $this->register(['phone' => null])->assertUnprocessable()->assertJsonValidationErrors('phone');
        $this->register(['phone' => 'call me'])->assertUnprocessable()->assertJsonValidationErrors('phone');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_phone_is_saved_on_the_user_and_member(): void
    {
        Mail::fake();

        $this->register()->assertCreated();

        $this->assertSame('+255 712 345 678', User::firstOrFail()->phone);
        $this->assertSame('+255 712 345 678', Member::firstOrFail()->phone);
    }

    public function test_verification_link_opens_the_member_app(): void
    {
        Mail::fake();
        config(['app.mobile_app_url' => 'https://members.example.test/']);

        $this->register()->assertCreated();

        Mail::assertSent(EmailVerificationMail::class, fn (EmailVerificationMail $mail) => $mail->verificationUrl
            === 'https://members.example.test/verify-email?email=phone%40example.test');
    }
}
