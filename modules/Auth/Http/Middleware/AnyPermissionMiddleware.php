<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Middleware;

use App\Modules\Auth\Support\AuthManager;
use Marwa\Router\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AnyPermissionMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ?string $permissions = null)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $permissions = $this->permissions ?? $request->getAttribute('required_permissions', '');
        $permissionList = array_filter(array_map('trim', explode(',', $permissions)));

        if ($permissionList === []) {
            return $handler->handle($request);
        }

        foreach ($permissionList as $permission) {
            if (app(AuthManager::class)->user()?->hasPermission($permission) === true) {
                return $handler->handle($request);
            }
        }

        return Response::json([
            'error' => 'Forbidden',
            'message' => 'You do not have any of the required permissions.',
            'required_permissions' => $permissionList,
        ], 403);
    }
}
