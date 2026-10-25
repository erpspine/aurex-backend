<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class EmailVerificationMail extends Mailable
{
    use Queueable;

    public function __construct(public string $name, public string $code, public string $recipientEmail = '')
    {
        $this->verificationUrl = rtrim(config('app.frontend_url'), '/').'/?verify=1&email='.urlencode($this->recipientEmail);
    }

    public string $verificationUrl;


    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Verify your AUREX email address');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.email-verification');
    }
}
