<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Middleware;

use App\Modules\Auth\Support\AuthManager;
use App\Modules\Auth\Support\RolePolicy;
use Marwa\Router\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MinimumLevelMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ?int $level = null)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $level = $this->level ?? (int) $request->getAttribute('minimum_level', 0);

        if ($level <= 0) {
            return $handler->handle($request);
        }

        $currentRole = app(AuthManager::class)->user()?->role();
        $currentLevel = $currentRole !== null ? RolePolicy::getRoleLevel((string) $currentRole->getAttribute('slug')) : 0;

        if ($currentLevel < $level) {
            return Response::json([
                'error' => 'Forbidden',
                'message' => "You don't have the required access level.",
                'required_level' => $level,
            ], 403);
        }

        return $handler->handle($request);
    }
}
