<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Marwa\Framework\Views\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AdminThemeMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var View $view */
        $view = app(View::class);
        $previousTheme = $view->theme();
        $adminTheme = trim((string) config('view.adminTheme', 'admin')) ?: 'admin';

        $view->theme($adminTheme);

        try {
            return $handler->handle($request);
        } finally {
            $view->theme($previousTheme);
        }
    }
}
