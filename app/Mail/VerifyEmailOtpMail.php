<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class VerifyEmailOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public string $userName;
    public int $expiresInMinutes;

    /**
     * Create a new message instance.
     */
    public function __construct(string $otp, string $userName = 'Pengguna MedScribe AI', int $expiresInMinutes = 10)
    {
        $this->otp = $otp;
        $this->userName = $userName;
        $this->expiresInMinutes = $expiresInMinutes;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $replyTo = [];
        $replyAddress = config('mail.reply_to.address');
        if ($replyAddress) {
            $replyTo[] = new Address(
                $replyAddress,
                config('mail.reply_to.name', 'MedScribe AI Support')
            );
        }

        return new Envelope(
            subject: 'Kode Verifikasi Email – MedScribe AI',
            replyTo: $replyTo,
        );
    }

    /**
     * Get the message content definition (HTML & Plain-Text Multipart).
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-email-otp',
            text: 'emails.verify-email-otp-text',
        );
    }

    /**
     * Get deliverability headers for transactional automated emails.
     */
    public function headers(): Headers
    {
        return new Headers(
            text: [
                'Auto-Submitted'           => 'auto-generated',
                'X-Auto-Response-Suppress' => 'All',
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
