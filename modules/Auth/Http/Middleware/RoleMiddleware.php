<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Middleware;

use App\Modules\Auth\Support\AuthManager;
use Marwa\Framework\Supports\Config;
use Marwa\Router\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RoleMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AuthManager $auth,
        private ?Config $config = null
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $this->auth->user();

        if ($user === null) {
            return Response::redirect('/auth/login', 303);
        }

        $role = $this->adminRole();

        if (!$this->auth->hasRole($user, $role)) {
            return Response::forbidden('Forbidden');
        }

        return $handler->handle($request);
    }

    private function adminRole(): string
    {
        if ($this->config instanceof Config) {
            $this->config->loadIfExists('auth.php');

            return (string) $this->config->get('auth.defaults.admin_role', 'admin');
        }

        return 'admin';
    }
}
