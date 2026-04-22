<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\View\Extensions\UserAgentViewExtension;
use PHPUnit\Framework\TestCase;

final class UserAgentViewExtensionTest extends TestCase
{
    public function testSummarizeReturnsCompactBrowserLabel(): void
    {
        $extension = new UserAgentViewExtension();

        self::assertSame(
            'Chrome 147.0.0.0 on Windows 10',
            $extension->summarize('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36')
        );
    }

    public function testSummarizeFallsBackToTruncationForUnknownAgents(): void
    {
        $extension = new UserAgentViewExtension();

        self::assertSame(
            'CustomAgent/1.2.3 Some Very Long Token That Needs T…',
            $extension->summarize('CustomAgent/1.2.3 Some Very Long Token That Needs Truncation', 52)
        );
    }

    public function testSummarizeHandlesEmptyValues(): void
    {
        $extension = new UserAgentViewExtension();

        self::assertSame('No browser info', $extension->summarize(''));
    }

    public function testFormatIpNormalizesIpv6AndFallsBackOnEmptyValues(): void
    {
        $extension = new UserAgentViewExtension();

        self::assertSame('2001:db8::1', $extension->formatIp('2001:0db8:0:0:0:0:0:1'));
        self::assertSame('Unknown IP', $extension->formatIp(''));
    }
}
