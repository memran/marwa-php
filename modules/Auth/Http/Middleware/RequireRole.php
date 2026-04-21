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

final class RequireRole implements MiddlewareInterface
{
    public function __construct(private readonly ?string $role = null)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $role = $this->role ?? $request->getAttribute('required_role');

        if ($role === null) {
            return $handler->handle($request);
        }

        $currentRole = app(AuthManager::class)->user()?->role();
        $currentSlug = $currentRole !== null ? (string) $currentRole->getAttribute('slug') : null;

        if (!RolePolicy::hasRole($currentSlug, $role)) {
            return Response::json([
                'error' => 'Forbidden',
                'message' => "You don't have the required role: {$role}",
                'required_role' => $role,
            ], 403);
        }

        return $handler->handle($request);
    }
}
