<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use Marwa\Framework\Application;
use PHPUnit\Framework\TestCase;
use App\Support\DebugbarCollectors;

final class AppConfigTest extends TestCase
{
    protected function setUp(): void
    {
        DebugbarCollectors::reset();
        $GLOBALS['marwa_app'] = new Application(dirname(__DIR__, 3));
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['marwa_app']);
        DebugbarCollectors::reset();
        putenv('DEBUGBAR_COLLECTORS');
        unset($_ENV['DEBUGBAR_COLLECTORS'], $_SERVER['DEBUGBAR_COLLECTORS']);

        parent::tearDown();
    }

    public function testAppConfigUsesDefaultDebugbarCollectorsWhenUnset(): void
    {
        $config = require dirname(__DIR__, 3) . '/config/app.php';

        self::assertIsArray($config);
        self::assertIsArray($config['collectors']);
        self::assertContains('Marwa\\DebugBar\\Collectors\\TimelineCollector', $config['collectors']);
        self::assertContains('Marwa\\DebugBar\\Collectors\\ExceptionCollector', $config['collectors']);
    }

    public function testAppConfigCanOverrideDebugbarCollectorsFromTheEnvironment(): void
    {
        $_ENV['DEBUGBAR_COLLECTORS'] = 'TimelineCollector,MemoryCollector';
        $_SERVER['DEBUGBAR_COLLECTORS'] = 'TimelineCollector,MemoryCollector';
        putenv('DEBUGBAR_COLLECTORS=TimelineCollector,MemoryCollector');

        $config = require dirname(__DIR__, 3) . '/config/app.php';

        self::assertSame([
            'Marwa\\DebugBar\\Collectors\\TimelineCollector',
            'Marwa\\DebugBar\\Collectors\\MemoryCollector',
        ], $config['collectors']);
    }

    public function testDebugbarCollectorsCanBeMergedGloballyByModules(): void
    {
        DebugbarCollectors::register('CustomCollector', 'Marwa\\DebugBar\\Collectors\\RequestCollector');

        $config = require dirname(__DIR__, 3) . '/config/app.php';
        $merged = DebugbarCollectors::merge($config['collectors']);

        self::assertSame([
            'Marwa\\DebugBar\\Collectors\\TimelineCollector',
            'Marwa\\DebugBar\\Collectors\\MemoryCollector',
            'Marwa\\DebugBar\\Collectors\\PhpCollector',
            'Marwa\\DebugBar\\Collectors\\RequestCollector',
            'Marwa\\DebugBar\\Collectors\\SessionCollector',
            'Marwa\\DebugBar\\Collectors\\LogCollector',
            'Marwa\\DebugBar\\Collectors\\ExceptionCollector',
            'Marwa\\DebugBar\\Collectors\\CustomCollector',
        ], $merged);
    }
}
