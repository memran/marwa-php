<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Marwa\Framework\Facades\View;
use Marwa\Router\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ApplicationLifecycleMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isFrontendRequest($request) || !$this->isMaintenanceModeEnabled()) {
            return $handler->handle($request);
        }

        $template = (string) config('app.maintenance.template', 'maintenance.twig');
        $message = (string) config('app.maintenance.message', 'Service temporarily unavailable for maintenance');
        $estimatedRecovery = date('c', time() + 300);

        if ($template !== '' && View::exists($template)) {
            return View::make($template, [
                'message' => $message,
                'estimated_recovery' => $estimatedRecovery,
                'app_name' => (string) config('settings.lifecycle.app.name', config('app.name', 'MarwaPHP')),
            ])->withStatus(503);
        }

        return Response::html(
            <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Maintenance</title>
                <style>
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        min-height: 100vh;
                        margin: 0;
                        background: #f5f5f5;
                        color: #333;
                    }
                    .container {
                        text-align: center;
                        padding: 2rem;
                    }
                    h1 {
                        font-size: 2rem;
                        margin-bottom: 1rem;
                        color: #e74c3c;
                    }
                    p {
                        font-size: 1.1rem;
                        color: #666;
                    }
                </style>
            </head>
            <body>
                <div class="container">
                    <h1>Under Maintenance</h1>
                    <p>{$message}</p>
                    <p>Estimated recovery: {$estimatedRecovery}</p>
                </div>
            </body>
            </html>
            HTML,
            503
        );
    }

    private function isMaintenanceModeEnabled(): bool
    {
        return (bool) config('settings.lifecycle.app.maintenance_mode', config('app.maintenance_mode', false));
    }

    private function isFrontendRequest(ServerRequestInterface $request): bool
    {
        return !str_starts_with(ltrim($request->getUri()->getPath(), '/'), 'admin');
    }
}
