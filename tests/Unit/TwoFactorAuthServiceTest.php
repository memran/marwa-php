<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Auth\Support\TwoFactorAuthService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TwoFactorAuthServiceTest extends TestCase
{
    /**
     * RFC 6238 Appendix B test key, base32-encoded.
     */
    private const RFC_KEY = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    public function testCodeAtKnownRfc6238Vector(): void
    {
        $service = new TwoFactorAuthService();

        self::assertSame('287082', $service->codeAtStep(self::RFC_KEY, intdiv(59, 30)));
        self::assertSame('081804', $service->codeAtStep(self::RFC_KEY, intdiv(1111111109, 30)));
        self::assertSame('050471', $service->codeAtStep(self::RFC_KEY, intdiv(1111111111, 30)));
        self::assertSame('005924', $service->codeAtStep(self::RFC_KEY, intdiv(1234567890, 30)));
        self::assertSame('279037', $service->codeAtStep(self::RFC_KEY, intdiv(2000000000, 30)));
        self::assertSame('353130', $service->codeAtStep(self::RFC_KEY, intdiv(20000000000, 30)));
    }

    public function testVerifyAcceptsValidCodeAndRejectsInvalid(): void
    {
        $service = new TwoFactorAuthService();

        self::assertTrue($service->verify(self::RFC_KEY, '287082', 59));
        self::assertTrue($service->verify(self::RFC_KEY, '081804', 1111111109));
        self::assertFalse($service->verify(self::RFC_KEY, '999999', 1111111109));
        self::assertFalse($service->verify(self::RFC_KEY, 'abcd', 1111111109));
    }

    public function testVerifyAcceptsCodeInsideClockSkewWindow(): void
    {
        $service = new TwoFactorAuthService();

        self::assertTrue($service->verify(self::RFC_KEY, '050471', 1111111109));
    }

    public function testGenerateSecretProducesValidBase32(): void
    {
        $service = new TwoFactorAuthService();
        $secret = $service->generateSecret();

        self::assertGreaterThanOrEqual(16, strlen($secret));
        self::assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
        self::assertTrue($service->verify($secret, $service->currentCode($secret)));
    }

    public function testBase32RoundTrip(): void
    {
        $service = new TwoFactorAuthService();
        $bytes = random_bytes(20);

        self::assertSame($bytes, $service->decodeBase32($service->encodeBase32($bytes)));
    }

    public function testProvisioningUriContainsExpectedParameters(): void
    {
        $service = new TwoFactorAuthService();
        $uri = $service->provisioningUri(self::RFC_KEY, 'john@example.com', 'MarwaPHP');

        self::assertStringStartsWith('otpauth://totp/', $uri);
        self::assertStringContainsString('secret=GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', $uri);
        self::assertStringContainsString('issuer=MarwaPHP', $uri);
        self::assertStringContainsString('algorithm=SHA1', $uri);
        self::assertStringContainsString('digits=6', $uri);
        self::assertStringContainsString('period=30', $uri);
    }

    public function testInvalidConfigurationRejected(): void
    {
        $this->expectException(RuntimeException::class);

        new TwoFactorAuthService(step: 0);
    }
}