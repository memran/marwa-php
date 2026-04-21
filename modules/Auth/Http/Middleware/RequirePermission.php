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
    {
    }

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

    /**
     * @param list<string> $permissions
     */
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
