<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Middleware;

use App\Modules\Auth\Support\AuthManager;
use Marwa\DB\Connection\ConnectionManager;
use Marwa\Router\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequireAdminAuthentication implements MiddlewareInterface
{
    public function __construct(private readonly AuthManager $auth)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!app()->has(ConnectionManager::class)) {
            return Response::redirect('/admin/login');
        }

        if (!$this->auth->check()) {
            return Response::redirect('/admin/login');
        }

        return $handler->handle($request);
    }
}
