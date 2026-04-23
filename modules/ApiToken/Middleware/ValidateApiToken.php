<?php

declare(strict_types=1);

namespace App\Modules\ApiToken\Middleware;

use App\Modules\ApiToken\Models\ApiToken;
use App\Modules\ApiToken\Support\ApiTokenRepositoryInterface;
use Marwa\Router\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ValidateApiToken implements MiddlewareInterface
{
    public function __construct(
        private readonly ApiTokenRepositoryInterface $repository
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $this->extractToken($request);

        if ($token === null) {
            return $this->unauthorized('API token is required.');
        }

        $apiToken = $this->repository->findByToken($token);

        if (!$apiToken instanceof ApiToken) {
            return $this->unauthorized('Invalid API token.');
        }

        if (!$apiToken->isActive()) {
            return $this->forbidden('API token has been revoked.');
        }

        $clientIp = $this->getClientIp($request);

        if ($apiToken->hasIpRestriction() && !$apiToken->isIpAllowed($clientIp)) {
            return $this->forbidden('API token is not allowed from this IP address.');
        }

        if ($this->repository->isRateLimited($apiToken)) {
            return $this->tooManyRequests($apiToken);
        }

        $this->repository->recordUsage((int) $apiToken->getKey());

        return $handler->handle(
            $request->withAttribute('api_token', $apiToken)
                ->withAttribute('api_token_id', $apiToken->getKey())
        );
    }

    public function handle(ServerRequestInterface $request): bool
    {
        return $this->extractToken($request) !== null;
    }

    private function extractToken(ServerRequestInterface $request): ?string
    {
        foreach (['Authorization', 'X-API-Token', 'X-Api-Token'] as $header) {
            $token = $this->tokenFromHeader($request->getHeaderLine($header), $header === 'Authorization');

            if ($token !== null) {
                return $token;
            }
        }

        return null;
    }

    private function tokenFromHeader(string $value, bool $isBearerHeader): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if ($isBearerHeader) {
            if (!str_starts_with($value, 'Bearer ')) {
                return null;
            }

            $value = trim(substr($value, 7));
        }

        if ($value === '' || !str_starts_with($value, 'sk_')) {
            return null;
        }

        return $value;
    }

    private function getClientIp(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();

        $ip = $serverParams['HTTP_X_FORWARDED_FOR']
            ?? $serverParams['HTTP_X_REAL_IP']
            ?? $serverParams['REMOTE_ADDR']
            ?? '127.0.0.1';

        $ip = trim(explode(',', (string) $ip)[0]);

        return filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : '127.0.0.1';
    }

    private function unauthorized(string $message): ResponseInterface
    {
        return Response::json([
            'success' => false,
            'message' => $message,
        ], 401, [
            'WWW-Authenticate' => 'Bearer',
        ]);
    }

    private function forbidden(string $message): ResponseInterface
    {
        return Response::json([
            'success' => false,
            'message' => $message,
        ], 403);
    }

    private function tooManyRequests(ApiToken $token): ResponseInterface
    {
        return Response::json([
            'success' => false,
            'message' => 'Too many requests.',
            'rate_limit' => $token->getRateLimit(),
        ], 429, [
            'Retry-After' => '60',
        ]);
    }
}
