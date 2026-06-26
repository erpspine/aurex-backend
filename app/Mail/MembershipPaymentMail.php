<?php

namespace App\Mail;

use App\Models\Member;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MembershipPaymentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Member $member,
        public Payment $payment,
        public string $renewalDate,
        public string $appUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Received - AUREX Performance Arena',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.membership-payment',
        );
    }
}
