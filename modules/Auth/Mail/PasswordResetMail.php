<?php

declare(strict_types=1);

namespace App\Modules\Auth\Mail;

use Marwa\Framework\Contracts\MailerInterface;
use Marwa\Framework\Mail\Mailable;
use Marwa\Framework\Views\View;

final class PasswordResetMail extends Mailable
{
    public function build(MailerInterface $mailer): MailerInterface
    {
        $appName = (string) $this->value('app_name', config('app.name', 'MarwaPHP'));
        $userEmail = $this->getValidEmail();
        $userName = $this->getValidName();
        $resetLink = (string) $this->value('reset_link', '');
        $expiresInMinutes = max(1, (int) $this->value('expires_in_minutes', 30));
        $expiresText = $expiresInMinutes === 1
            ? '1 minute'
            : $expiresInMinutes . ' minutes';

        /** @var View $view */
        $view = app(View::class);
        $htmlTemplate = $view->render('@Shared/email/password-reset-html.twig', [
            'app_name' => $appName,
            'user_name' => $userName,
            'reset_link' => $resetLink,
            'expires_text' => $expiresText,
        ]);
        $textTemplate = $view->render('@Shared/email/password-reset-text.twig', [
            'app_name' => $appName,
            'user_name' => $userName,
            'reset_link' => $resetLink,
            'expires_text' => $expiresText,
        ]);

        return $mailer
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->to($userEmail, $userName)
            ->subject("Password reset request - {$appName}")
            ->html($htmlTemplate, $textTemplate)
        ;
    }

    public function getValidEmail(): string
    {
        $email = trim((string) $this->value('user_email', ''));

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new \RuntimeException('Invalid or empty email address for the queued password reset.');
        }

        return strtolower($email);
    }

    public function getValidName(): string
    {
        $name = trim((string) $this->value('user_name', ''));
        $name = preg_replace('/[\x00-\x1F\x7F]/', '', $name);
        $name = htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $name;
    }
}
