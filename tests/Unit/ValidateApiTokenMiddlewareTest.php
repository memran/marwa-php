<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\ApiToken\Middleware\ValidateApiToken;
use App\Modules\ApiToken\Models\ApiToken;
use App\Modules\ApiToken\Support\ApiTokenRepositoryInterface;
use Laminas\Diactoros\ServerRequest;
use Marwa\Router\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ValidateApiTokenMiddlewareTest extends TestCase
{
    public function testMissingTokenIsRejected(): void
    {
        $repository = $this->createMock(ApiTokenRepositoryInterface::class);
        $middleware = new ValidateApiToken($repository);
        $request = new ServerRequest(serverParams: ['REMOTE_ADDR' => '127.0.0.1']);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $repository->expects(self::never())->method('findByToken');
        $repository->expects(self::never())->method('recordUsage');
        $repository->expects(self::never())->method('isRateLimited');
        $handler->expects(self::never())->method('handle');

        $response = $middleware->process($request, $handler);

        self::assertSame(401, $response->getStatusCode());
    }

    public function testValidTokenIsAttachedToTheRequest(): void
    {
        $repository = $this->createMock(ApiTokenRepositoryInterface::class);
        $middleware = new ValidateApiToken($repository);
        $token = ApiToken::newInstance([
            'id' => 5,
            'name' => 'Integration Token',
            'token_hash' => hash('sha256', 'sk_test_123'),
            'token_prefix' => 'sk_test_',
            'allowed_ips' => [],
            'rate_limit' => 10,
            'is_active' => 1,
        ], true);

        $request = $this->createMock(ServerRequestInterface::class);
        $requestWithToken = $this->createMock(ServerRequestInterface::class);
        $requestWithTokenAndId = $this->createMock(ServerRequestInterface::class);
        $response = Response::json(['ok' => true]);
        $handler = $this->createMock(RequestHandlerInterface::class);

        $request->expects(self::once())
            ->method('getHeaderLine')
            ->with('Authorization')
            ->willReturn('Bearer sk_test_123');
        $request->expects(self::once())
            ->method('getServerParams')
            ->willReturn(['REMOTE_ADDR' => '127.0.0.1']);
        $request->expects(self::once())
            ->method('withAttribute')
            ->with('api_token', $token)
            ->willReturn($requestWithToken);

        $requestWithToken->expects(self::once())
            ->method('withAttribute')
            ->with('api_token_id', 5)
            ->willReturn($requestWithTokenAndId);

        $repository->expects(self::once())
            ->method('findByToken')
            ->with('sk_test_123')
            ->willReturn($token);
        $repository->expects(self::once())
            ->method('isRateLimited')
            ->with($token)
            ->willReturn(false);
        $repository->expects(self::once())
            ->method('recordUsage')
            ->with(5);

        $handler->expects(self::once())
            ->method('handle')
            ->with($requestWithTokenAndId)
            ->willReturn($response);

        self::assertSame($response, $middleware->process($request, $handler));
    }

    public function testRateLimitedTokenGetsTooManyRequestsResponse(): void
    {
        $repository = $this->createMock(ApiTokenRepositoryInterface::class);
        $middleware = new ValidateApiToken($repository);
        $token = ApiToken::newInstance([
            'id' => 9,
            'name' => 'Limited Token',
            'token_hash' => hash('sha256', 'sk_limited_123'),
            'token_prefix' => 'sk_limit',
            'allowed_ips' => [],
            'rate_limit' => 1,
            'is_active' => 1,
        ], true);

        $request = new ServerRequest(
            serverParams: ['REMOTE_ADDR' => '127.0.0.1'],
            headers: ['Authorization' => 'Bearer sk_limited_123']
        );
        $handler = $this->createMock(RequestHandlerInterface::class);

        $repository->expects(self::once())
            ->method('findByToken')
            ->with('sk_limited_123')
            ->willReturn($token);
        $repository->expects(self::once())
            ->method('isRateLimited')
            ->with($token)
            ->willReturn(true);
        $repository->expects(self::never())
            ->method('recordUsage');
        $handler->expects(self::never())
            ->method('handle');

        $response = $middleware->process($request, $handler);

        self::assertSame(429, $response->getStatusCode());
        self::assertSame('60', $response->getHeaderLine('Retry-After'));
    }
}
