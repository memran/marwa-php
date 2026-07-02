<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\ApiToken\Support\ApiTokenFormData;
use PHPUnit\Framework\TestCase;

final class ApiTokenFormDataTest extends TestCase
{
    public function testNormalizeParsesAllowedIpsAndCidrRanges(): void
    {
        $forms = new ApiTokenFormData();

        $payload = $forms->normalize([
            'name' => ' Production ',
            'allowed_ips' => "192.168.1.10\n10.0.0.0/24\ninvalid\n10.0.0.0/99",
            'rate_limit' => '120',
        ]);

        self::assertSame('Production', $payload['name']);
        self::assertSame(['192.168.1.10', '10.0.0.0/24'], $payload['allowed_ips']);
        self::assertSame(['invalid', '10.0.0.0/99'], $payload['invalid_ips']);
        self::assertSame(120, $payload['rate_limit']);
    }
}
