<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Middleware;

use App\Modules\Auth\Support\Gate;
use Marwa\Router\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequirePermission implements MiddlewareInterface
{
    private Gate $gate;

    public function __construct()
    {
        $this->gate = app(Gate::class);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $permission = $request->getAttribute('required_permission');

        if ($permission === null) {
            return $handler->handle($request);
        }

        if (!$this->gate->allows($permission)) {
            return Response::json([
                'error' => 'Forbidden',
                'message' => "You don't have permission to access this resource.",
                'required_permission' => $permission,
            ], 403);
        }

        return $handler->handle($request);
    }

    public static function forPermission(string $permission): array
    {
        return [self::class, $permission];
    }

    public static function forAnyPermission(array $permissions): array
    {
        return [AnyPermissionMiddleware::class, implode(',', $permissions)];
    }

    public static function forRole(string $role): array
    {
        return [RequireRole::class, $role];
    }

    public static function forMinimumLevel(int $level): array
    {
        return [MinimumLevelMiddleware::class, $level];
    }
}

final class AnyPermissionMiddleware implements MiddlewareInterface
{
    private Gate $gate;

    public function __construct()
    {
        $this->gate = app(Gate::class);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $permissions = $request->getAttribute('required_permissions', '');
        $permissionList = array_filter(array_map('trim', explode(',', $permissions)));

        if (empty($permissionList)) {
            return $handler->handle($request);
        }

        foreach ($permissionList as $permission) {
            if ($this->gate->allows($permission)) {
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
    private Gate $gate;

    public function __construct()
    {
        $this->gate = app(Gate::class);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $role = $request->getAttribute('required_role');

        if ($role === null) {
            return $handler->handle($request);
        }

        if (!$this->gate->hasRole($role)) {
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
    private Gate $gate;

    public function __construct()
    {
        $this->gate = app(Gate::class);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $level = (int) $request->getAttribute('minimum_level', 0);

        if ($level <= 0) {
            return $handler->handle($request);
        }

        if (!$this->gate->isAtLevel($level)) {
            return Response::json([
                'error' => 'Forbidden',
                'message' => "You don't have the required access level.",
                'required_level' => $level,
            ], 403);
        }

        return $handler->handle($request);
    }
}