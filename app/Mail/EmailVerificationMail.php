<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class EmailVerificationMail extends Mailable
{
    use Queueable;

    public string $verificationUrl;

    public function __construct(public string $name, public string $code, public string $recipientEmail = '')
    {
        // Members sign up in the mobile app, so the link opens the app's /verify-email page
        // (MOBILE_APP_URL falls back to FRONTEND_URL, which handles the same path).
        $this->verificationUrl = rtrim((string) config('app.mobile_app_url'), '/')
            .'/verify-email?email='.urlencode($this->recipientEmail);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Verify your AUREX email address');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.email-verification');
    }
}
