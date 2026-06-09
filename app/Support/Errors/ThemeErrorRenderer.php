<?php

declare(strict_types=1);

namespace App\Support\Errors;

use Marwa\ErrorHandler\Contracts\RendererInterface;
use Marwa\ErrorHandler\Support\FallbackRenderer;
use Marwa\Framework\Facades\View;
use Throwable;

final class ThemeErrorRenderer implements RendererInterface
{
    private const TEMPLATE_KEY = 'app.error500.template';

    private FallbackRenderer $fallback;

    public function __construct()
    {
        $this->fallback = new FallbackRenderer();
    }

    public function renderException(Throwable $e, string $appName, bool $dev): void
    {
        if ($this->renderTemplate($appName, $dev, $e)) {
            return;
        }

        $this->fallback->renderException($e, $appName, $dev);
    }

    public function renderGeneric(string $appName): void
    {
        if ($this->renderTemplate($appName, false, null)) {
            return;
        }

        $this->fallback->renderGeneric($appName);
    }

    public function renderCli(Throwable $e, string $appName, bool $dev): void
    {
        $this->fallback->renderCli($e, $appName, $dev);
    }

    /**
     * @return list<array{file:string,line:int,call:string}>
     */
    private function trace(Throwable $throwable): array
    {
        $frames = [];

        foreach ($throwable->getTrace() as $frame) {
            $frames[] = [
                'file' => (string) ($frame['file'] ?? '-'),
                'line' => (int) ($frame['line'] ?? 0),
                'call' => (string) (($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '')),
            ];

            if (count($frames) >= 12) {
                break;
            }
        }

        return $frames;
    }

    private function renderTemplate(string $appName, bool $dev, ?Throwable $throwable): bool
    {
        $template = (string) config(self::TEMPLATE_KEY, 'errors/500.twig');

        if ($template === '' || !View::exists($template)) {
            return false;
        }

        $this->sendHtmlHeaders();

        echo View::render($template, [
            'app_name' => $appName,
            'debug' => $dev,
            'request_id' => $this->requestId(),
            'timestamp' => gmdate('Y-m-d H:i:s \U\T\C'),
            'message' => $dev && $throwable instanceof Throwable
                ? $throwable->getMessage()
                : 'We could not complete your request right now.',
            'exception_class' => $throwable instanceof Throwable ? $throwable::class : null,
            'exception_file' => $throwable instanceof Throwable ? $throwable->getFile() : null,
            'exception_line' => $throwable instanceof Throwable ? $throwable->getLine() : null,
            'trace' => $throwable instanceof Throwable ? $this->trace($throwable) : [],
        ]);

        return true;
    }

    private function sendHtmlHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store, private');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
    }

    private function requestId(): string
    {
        foreach (['HTTP_X_REQUEST_ID', 'HTTP_X_CORRELATION_ID'] as $headerName) {
            $candidate = $_SERVER[$headerName] ?? null;

            if (is_string($candidate) && preg_match('/\A[a-zA-Z0-9._:-]{1,128}\z/', $candidate) === 1) {
                return $candidate;
            }
        }

        try {
            return 'r-' . bin2hex(random_bytes(6));
        } catch (Throwable) {
            return 'r-' . str_replace('.', '', uniqid('', true));
        }
    }
}
