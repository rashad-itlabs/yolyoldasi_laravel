<?php

namespace App\Mail;

use App\Models\EmailCode;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Sayt qeydiyyatı və şifrə bərpası kodu.
 *
 * Növbəyə qoyulmur (`ShouldQueue` yoxdur): serverdə növbə işçisi yoxdur və
 * kod istifadəçi ekranda gözləyərkən getməlidir.
 */
class EmailCodeMail extends Mailable
{
    public function __construct(
        public string $code,
        public string $purpose,
        public ?string $name = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->purpose === 'reset'
                ? "Şifrə bərpası kodu: {$this->code}"
                : "Təsdiq kodu: {$this->code}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.code',
            with: ['minutes' => EmailCode::TTL_MINUTES],
        );
    }
}
