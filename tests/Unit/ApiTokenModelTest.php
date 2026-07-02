<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\ApiToken\Models\ApiToken;
use PHPUnit\Framework\TestCase;

final class ApiTokenModelTest extends TestCase
{
    public function testInvalidCidrRangeDoesNotMatchOrThrow(): void
    {
        $token = ApiToken::newInstance([
            'allowed_ips' => ['10.0.0.0/99'],
            'is_active' => 1,
        ], true);

        self::assertFalse($token->isIpAllowed('10.0.0.10'));
    }

    public function testValidCidrRangeMatchesIpv4Address(): void
    {
        $token = ApiToken::newInstance([
            'allowed_ips' => ['10.0.0.0/24'],
            'is_active' => 1,
        ], true);

        self::assertTrue($token->isIpAllowed('10.0.0.10'));
        self::assertFalse($token->isIpAllowed('10.0.1.10'));
    }
}
