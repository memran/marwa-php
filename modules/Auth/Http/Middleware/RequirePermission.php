<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Middleware;

use App\Modules\Auth\Support\AuthManager;
use Marwa\Router\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequirePermission implements MiddlewareInterface
{
    public function __construct(private readonly ?string $permission = null)
    {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $permission = $this->permission ?? $request->getAttribute('required_permission');

        if ($permission === null) {
            return $handler->handle($request);
        }

        if (!$this->currentUserHasPermission($permission)) {
            return Response::json([
                'error' => 'Forbidden',
                'message' => "You don't have permission to access this resource.",
                'required_permission' => $permission,
            ], 403);
        }

        return $handler->handle($request);
    }

    public static function forPermission(string $permission): self
    {
        return new self($permission);
    }

    public static function forAnyPermission(array $permissions): AnyPermissionMiddleware
    {
        return new AnyPermissionMiddleware(implode(',', $permissions));
    }

    public static function forRole(string $role): RequireRole
    {
        return new RequireRole($role);
    }

    public static function forMinimumLevel(int $level): MinimumLevelMiddleware
    {
        return new MinimumLevelMiddleware($level);
    }

    private function currentUserHasPermission(string $permission): bool
    {
        $user = app(AuthManager::class)->user();

        return $user !== null && $user->hasPermission($permission);
    }
}

final class AnyPermissionMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ?string $permissions = null)
    {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $permissions = $this->permissions ?? $request->getAttribute('required_permissions', '');
        $permissionList = array_filter(array_map('trim', explode(',', $permissions)));

        if (empty($permissionList)) {
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

final class RequireRole implements MiddlewareInterface
{
    public function __construct(private readonly ?string $role = null)
    {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $role = $this->role ?? $request->getAttribute('required_role');

        if ($role === null) {
            return $handler->handle($request);
        }

        $currentRole = app(\App\Modules\Auth\Support\AuthManager::class)->user()?->role();

        if ($currentRole === null || !\App\Modules\Auth\Support\RolePolicy::hasRole((string) $currentRole->getAttribute('slug'), $role)) {
            return Response::json([
                'error' => 'Forbidden',
                'message' => "You don't have the required role: {$role}",
                'required_role' => $role,
            ], 403);
        }

        return $handler->handle($request);
    }
}

final class MinimumLevelMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ?int $level = null)
    {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $level = $this->level ?? (int) $request->getAttribute('minimum_level', 0);

        if ($level <= 0) {
            return $handler->handle($request);
        }

        $currentRole = app(\App\Modules\Auth\Support\AuthManager::class)->user()?->role();
        $currentLevel = $currentRole !== null ? \App\Modules\Auth\Support\RolePolicy::getRoleLevel((string) $currentRole->getAttribute('slug')) : 0;

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
