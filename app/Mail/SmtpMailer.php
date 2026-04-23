<?php

declare(strict_types=1);

namespace App\Mail;

use Swift_SmtpTransport;
use Swift_Mailer;
use Swift_Message;
use Swift_Attachment;

final class SmtpMailer
{
    private ?Swift_SmtpTransport $transport = null;
    private ?Swift_Mailer $mailer = null;
    private ?Swift_Message $message = null;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly ?string $username,
        private readonly ?string $password,
        private readonly ?string $encryption,
        private readonly int $timeout,
        private readonly string $charset,
        private readonly string $fromAddress,
        private readonly string $fromName,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            host: (string) config('mail.smtp.host', '127.0.0.1'),
            port: (int) config('mail.smtp.port', 1025),
            username: (string) config('mail.smtp.username', ''),
            password: (string) config('mail.smtp.password', ''),
            encryption: (string) config('mail.smtp.encryption', ''),
            timeout: (int) config('mail.smtp.timeout', 30),
            charset: (string) config('mail.charset', 'UTF-8'),
            fromAddress: (string) config('mail.from.address', 'no-reply@example.com'),
            fromName: (string) config('mail.from.name', 'MarwaPHP'),
        );
    }

    public function subject(string $subject): self
    {
        $this->ensureMessage();
        $this->message->setSubject($subject);

        return $this;
    }

    public function from(string $address, ?string $name = null): self
    {
        $this->ensureMessage();
        $this->message->setFrom($address, $name ?? '');

        return $this;
    }

    public function to(string $address, ?string $name = null): self
    {
        $this->ensureMessage();
        $this->message->addTo($address, $name ?? '');

        return $this;
    }

    public function html(string $html, ?string $text = null): self
    {
        $this->ensureMessage();
        $this->message->setBody($html, 'text/html', $this->charset);

        if ($text !== null) {
            $this->message->addPart($text, 'text/plain', $this->charset);
        }

        return $this;
    }

    public function text(string $text): self
    {
        $this->ensureMessage();
        $this->message->setBody($text, 'text/plain', $this->charset);

        return $this;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function renderEmailTemplate(string $template, array $data = []): string
    {
        /** @var \Marwa\View\View $view */
        $view = app()->make(\Marwa\View\View::class);

        return $view->render($template, $data);
    }

    public function attach(string $path, ?string $name = null, string $mime = 'application/octet-stream'): self
    {
        $this->ensureMessage();
        $attachment = Swift_Attachment::fromPath($path, $mime);

        if ($name !== null) {
            $attachment->setFilename($name);
        }

        $this->message->attach($attachment);

        return $this;
    }

    public function send(): int
    {
        $this->ensureMessage();

        if ($this->message->getFrom() === []) {
            $this->message->setFrom([$this->fromAddress => $this->fromName]);
        }

        $mailer = $this->getMailer();
        $sent = $mailer->send($this->message);

        $this->reset();

        return $sent;
    }

    public function reset(): self
    {
        $this->message = null;
        $this->transport = null;
        $this->mailer = null;

        return $this;
    }

    private function ensureMessage(): void
    {
        if ($this->message !== null) {
            return;
        }

        $this->message = (new Swift_Message())
            ->setCharset($this->charset)
            ->setFrom([$this->fromAddress => $this->fromName]);
    }

    private function getTransport(): Swift_SmtpTransport
    {
        if ($this->transport !== null) {
            return $this->transport;
        }

        $this->transport = new Swift_SmtpTransport($this->host, $this->port);

        if ($this->encryption !== '') {
            $this->transport->setEncryption($this->encryption);
        }

        if ($this->username !== '') {
            $this->transport->setUsername($this->username);
        }

        if ($this->password !== '') {
            $this->transport->setPassword($this->password);
        }

        if ($this->timeout > 0) {
            $this->transport->setTimeout($this->timeout);
        }

        return $this->transport;
    }

    private function getMailer(): Swift_Mailer
    {
        return $this->mailer ??= new Swift_Mailer($this->getTransport());
    }
}