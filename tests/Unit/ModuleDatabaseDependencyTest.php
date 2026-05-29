<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\ModuleDatabaseDependency;
use Marwa\Framework\Application;
use PHPUnit\Framework\TestCase;

final class ModuleDatabaseDependencyTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-module-db-dependency-' . bin2hex(random_bytes(6));

        mkdir($this->basePath, 0777, true);
        file_put_contents($this->basePath . '/.env', "APP_ENV=testing\nAPP_KEY=0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef\n");
    }

    protected function tearDown(): void
    {
        foreach ([
            $this->basePath . '/.env',
        ] as $file) {
            @unlink($file);
        }

        @rmdir($this->basePath);

        unset(
            $GLOBALS['marwa_app'],
            $_ENV['APP_ENV'],
            $_ENV['APP_KEY'],
            $_SERVER['APP_ENV'],
            $_SERVER['APP_KEY']
        );

        parent::tearDown();
    }

    public function test_it_detects_database_dependent_modules_from_the_manifest(): void
    {
        self::assertTrue(ModuleDatabaseDependency::requiresDatabase(dirname(__DIR__, 2) . '/modules/Auth'));
        self::assertTrue(ModuleDatabaseDependency::requiresDatabase(dirname(__DIR__, 2) . '/modules/Users'));
        self::assertFalse(ModuleDatabaseDependency::requiresDatabase(dirname(__DIR__, 2) . '/modules/DashboardStatus'));
    }

    public function test_it_skips_boot_for_database_dependent_modules_without_a_connection(): void
    {
        $called = false;
        $app = new Application($this->basePath);

        ModuleDatabaseDependency::boot(
            dirname(__DIR__, 2) . '/modules/Auth',
            $app,
            static function () use (&$called): void {
                $called = true;
            }
        );

        self::assertFalse($called);
    }

    public function test_it_runs_boot_for_non_database_modules_even_without_a_connection(): void
    {
        $called = false;
        $app = new Application($this->basePath);

        ModuleDatabaseDependency::boot(
            dirname(__DIR__, 2) . '/modules/DashboardStatus',
            $app,
            static function () use (&$called): void {
                $called = true;
            }
        );

        self::assertTrue($called);
    }
}
