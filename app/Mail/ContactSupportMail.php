<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactSupportMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $userSubject,
        public readonly string $userMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [$this->user->email],
            subject: "Support Request: {$this->userSubject}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.contact-support',
        );
    }
}
