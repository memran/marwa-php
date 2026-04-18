<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Middleware;

use App\Modules\Auth\Support\Gate;
use Marwa\Router\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequireAdminRole implements MiddlewareInterface
{
    public function __construct(private readonly Gate $gate)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->gate->hasRole('admin')) {
            return Response::json([
                'message' => 'Forbidden',
            ], 403);
        }

        return $handler->handle($request);
    }
}
