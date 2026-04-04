<?php

declare(strict_types=1);

namespace App\Modules\Auth\Mail;

use Marwa\Framework\Contracts\MailerInterface;
use Marwa\Framework\Mail\Mailable;

final class PasswordResetMail extends Mailable
{
    public function build(MailerInterface $mailer): MailerInterface
    {
        $html = app(\Marwa\Framework\Views\View::class)->render('@auth/emails/password-reset', [
            'url' => (string) $this->value('url', ''),
            'name' => (string) $this->value('name', 'there'),
            'ttl' => (int) $this->value('ttl', 3600),
        ]);

        return $mailer
            ->subject('Reset your MarwaPHP password')
            ->to((array) $this->value('to', []))
            ->html($html, 'Reset your MarwaPHP password');
    }
}
