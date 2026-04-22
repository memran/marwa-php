<?php

declare(strict_types=1);

namespace App\Modules\Activity\Support;

use App\Modules\Auth\Support\AuthManager;
use App\Modules\Users\Models\User;
use Marwa\Framework\Adapters\Event\RequestHandled;
use Psr\Http\Message\ServerRequestInterface;

final class AdminActivityTrail
{
    private const SKIPPED_CONTROLLERS = [
        'App\\Modules\\Auth\\Http\\Controllers\\AuthController',
    ];

    public function __construct(
        private readonly ActivityRecorder $recorder,
        private readonly AuthManager $auth,
    ) {}

    public function record(RequestHandled $event): void
    {
        $request = $this->request();
        if ($request === null || !$this->shouldRecord($request, $event)) {
            return;
        }

        $route = $this->matchRoute($request);
        if ($route === null || $this->shouldSkipController($route['controller'] ?? null)) {
            return;
        }

        $module = $this->moduleSlug((string) ($route['controller'] ?? ''));
        $operation = $this->operation((string) ($route['action'] ?? ''), $request->getMethod());
        if ($operation === null) {
            return;
        }

        if ($this->activityFlag($module) !== true) {
            return;
        }

        $resource = $this->resourceName($module, (string) ($route['action'] ?? ''));
        $description = $this->description($operation, $resource);
        $subjectId = $this->subjectId($route['params'] ?? []);

        $details = [
            'summary' => $description,
            'module' => $module,
            'route' => (string) ($route['path'] ?? ''),
            'controller' => (string) ($route['controller'] ?? ''),
            'action' => (string) ($route['action'] ?? ''),
            'method' => $event->method,
            'status' => $event->statusCode,
            'request' => $this->filteredRequestData($request),
        ];

        $this->recorder->recordActorAction(
            $module . '.' . $operation,
            $description,
            $this->actor(),
            $resource,
            $subjectId,
            $details
        );
    }

    private function activityFlag(string $module): mixed
    {
        $key = $module . '.activity';

        try {
            return app()->container()->get($key);
        } catch (\Throwable) {
            return null;
        }
    }

    private function request(): ?ServerRequestInterface
    {
        try {
            if (!app()->has(ServerRequestInterface::class)) {
                return null;
            }

            /** @var ServerRequestInterface $request */
            $request = app(ServerRequestInterface::class);

            return $request;
        } catch (\Throwable) {
            return null;
        }
    }

    private function actor(): ?User
    {
        return $this->auth->user() instanceof User ? $this->auth->user() : null;
    }

    private function shouldRecord(ServerRequestInterface $request, RequestHandled $event): bool
    {
        if ($event->statusCode >= 400) {
            return false;
        }

        $method = strtoupper($request->getMethod());
        $path = $request->getUri()->getPath();

        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true) && $path !== '/admin/logout') {
            return false;
        }

        return true;
    }

    /**
     * @return array{controller:?string,action:?string,path:?string,params:array<string,string>}|null
     */
    private function matchRoute(ServerRequestInterface $request): ?array
    {
        $router = router();
        if (!is_object($router) || !method_exists($router, 'routes')) {
            return null;
        }

        $routes = $router->routes();
        $method = strtoupper($request->getMethod());
        $path = $this->normalizePath($request->getUri()->getPath());

        foreach ($routes as $route) {
            $methods = array_map(
                static fn (mixed $value): string => strtoupper(trim((string) $value)),
                is_array($route['methods'] ?? null) ? $route['methods'] : []
            );

            if ($methods !== [] && !in_array($method, $methods, true)) {
                continue;
            }

            $pattern = $this->routePattern((string) ($route['path'] ?? ''));
            if ($pattern === null || !preg_match($pattern, $path, $matches)) {
                continue;
            }

            return [
                'controller' => is_string($route['controller'] ?? null) ? $route['controller'] : null,
                'action' => is_string($route['action'] ?? null) ? $route['action'] : null,
                'path' => is_string($route['path'] ?? null) ? $route['path'] : null,
                'params' => $this->routeParams($matches),
            ];
        }

        return null;
    }

    private function shouldSkipController(?string $controller): bool
    {
        if (!is_string($controller) || $controller === '') {
            return true;
        }

        return in_array($controller, self::SKIPPED_CONTROLLERS, true);
    }

    private function moduleSlug(string $controller): string
    {
        if (preg_match('/^App\\\\Modules\\\\([^\\\\]+)\\\\/', $controller, $matches) !== 1) {
            return 'module';
        }

        return strtolower($this->slugify($matches[1]));
    }

    private function resourceName(string $module, string $action): string
    {
        $map = [
            'users' => 'user',
            'roles' => 'role',
            'permissions' => 'permission',
            'settings' => 'settings',
            'notifications' => 'notification',
            'dashboard' => 'dashboard',
            'database-manager' => 'database',
            'auth' => 'auth',
        ];

        if (isset($map[$module])) {
            return $map[$module];
        }

        $resource = $this->operationResource($action);

        return $resource !== '' ? $resource : $module;
    }

    private function operation(string $action, string $method): ?string
    {
        return match ($action) {
            'store' => 'created',
            'update' => 'updated',
            'destroy', 'delete' => 'deleted',
            'restore' => 'restored',
            'saveWidgets' => 'saved',
            'reset' => 'reset',
            'purgeCache' => 'cache_cleared',
            'clearLogs' => 'logs_cleared',
            'execute' => 'executed',
            default => $this->defaultOperation($action, $method),
        };
    }

    private function defaultOperation(string $action, string $method): ?string
    {
        if ($action === '' || in_array(strtoupper($method), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return null;
        }

        return strtolower($action);
    }

    private function operationResource(string $action): string
    {
        return match ($action) {
            'saveWidgets', 'reset' => 'dashboard',
            'purgeCache', 'clearLogs' => 'settings',
            'execute' => 'database',
            default => '',
        };
    }

    private function description(string $operation, string $resource): string
    {
        $verb = match ($operation) {
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'restored' => 'Restored',
            'saved' => 'Saved',
            'reset' => 'Reset',
            'cache_cleared' => 'Cleared cache for',
            'logs_cleared' => 'Cleared logs for',
            'executed' => 'Executed',
            default => ucfirst(str_replace('_', ' ', $operation)),
        };

        if (in_array($operation, ['cache_cleared', 'logs_cleared', 'executed'], true)) {
            return sprintf('%s %s.', $verb, $resource);
        }

        return sprintf('%s %s.', $verb, $resource);
    }

    /**
     * @param array<string, string> $params
     */
    private function subjectId(array $params): ?int
    {
        foreach ($params as $value) {
            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    private function filteredRequestData(ServerRequestInterface $request): array
    {
        $parsed = $request->getParsedBody();

        if (!is_array($parsed)) {
            return [];
        }

        return $this->sanitizePayload($parsed);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function sanitizePayload(array $payload): array
    {
        $safe = [];

        foreach ($payload as $key => $value) {
            $name = strtolower((string) $key);

            if (in_array($name, ['_token', 'password', 'password_confirmation', 'current_password', 'new_password', 'confirm_destructive'], true)) {
                continue;
            }

            if (is_array($value)) {
                $safe[(string) $key] = $this->sanitizePayload($value);
                continue;
            }

            $safe[(string) $key] = is_scalar($value) ? trim((string) $value) : null;
        }

        return $safe;
    }

    private function routePattern(string $path): ?string
    {
        $path = $this->normalizePath($path);

        if ($path === '') {
            return null;
        }

        $segments = array_values(array_filter(explode('/', trim($path, '/')), static fn (string $segment): bool => $segment !== ''));
        $regex = '#^';

        foreach ($segments as $segment) {
            $regex .= '/';

            if (preg_match('/^\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}$/', $segment, $matches) === 1) {
                $name = $matches[1];
                $pattern = $matches[2] ?? '[^/]+';
                $regex .= '(?P<' . $name . '>' . $pattern . ')';
                continue;
            }

            $regex .= preg_quote($segment, '#');
        }

        $regex .= '/?$#';

        return $regex;
    }

    /**
     * @param array<string, string> $matches
     * @return array<string, string>
     */
    private function routeParams(array $matches): array
    {
        $params = [];

        foreach ($matches as $key => $value) {
            if (!is_string($key) || $key === '' || is_array($value)) {
                continue;
            }

            $params[$key] = (string) $value;
        }

        return $params;
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private function slugify(string $value): string
    {
        $value = preg_replace('/([a-z])([A-Z])/', '$1-$2', $value) ?? $value;
        $value = preg_replace('/[^A-Za-z0-9]+/', '-', $value) ?? $value;

        return strtolower(trim($value, '-'));
    }
}
