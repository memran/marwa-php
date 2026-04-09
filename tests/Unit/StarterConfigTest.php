<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class StarterConfigTest extends TestCase
{
    public function testAppConfigDefinesStarterErrorPagesAndBooleanDebugbar(): void
    {
        $config = require __DIR__ . '/../../config/app.php';

        self::assertIsArray($config);
        self::assertArrayHasKey('debugbar', $config);
        self::assertIsBool($config['debugbar']);
        self::assertSame('maintenance.twig', $config['maintenance']['template']);
        self::assertSame('errors/404.twig', $config['error404']['template']);
    }
}
