<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Marwa\Router\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class NormalizeTrailingSlashMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        if ($path === '/' || !str_ends_with($path, '/')) {
            return $handler->handle($request);
        }

        if ($path === '/admin/') {
            return Response::redirect('/admin/dashboard', 302);
        }

        $normalizedPath = rtrim($path, '/');
        if ($normalizedPath === '') {
            $normalizedPath = '/';
        }

        $query = $request->getUri()->getQuery();
        if ($query !== '') {
            $normalizedPath .= '?' . $query;
        }

        return Response::redirect($normalizedPath, 301);
    }
}
