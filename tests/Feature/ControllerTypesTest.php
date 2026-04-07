<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

final class ControllerTypesTest extends TestCase
{
    public function testFrontendAndBackendControllersAreSeparated(): void
    {
        $basePath = dirname(__DIR__, 2);

        $frontend = file_get_contents($basePath . '/app/Controllers/FrontendController.php');
        $backend = file_get_contents($basePath . '/app/Controllers/BackendController.php');
        $home = file_get_contents($basePath . '/app/Controllers/HomeController.php');
        $admin = file_get_contents($basePath . '/app/Controllers/AdminController.php');

        self::assertIsString($frontend);
        self::assertIsString($backend);
        self::assertIsString($home);
        self::assertIsString($admin);
        self::assertStringContainsString('abstract class FrontendController', $frontend);
        self::assertStringContainsString('abstract class BackendController', $backend);
        self::assertStringContainsString('extends FrontendController', $home);
        self::assertStringContainsString('extends BackendController', $admin);
    }
}
