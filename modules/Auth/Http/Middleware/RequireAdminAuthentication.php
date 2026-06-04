<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Middleware;

use Marwa\Router\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequireAdminAuthentication implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!app(\App\Modules\Auth\Support\AuthManager::class)->check()) {
            return Response::redirect('/admin/login');
        }

        return $handler->handle($request);
    }
}
