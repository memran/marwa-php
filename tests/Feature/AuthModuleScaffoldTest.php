<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

final class AuthModuleScaffoldTest extends TestCase
{
    public function testAuthModuleConfigurationAndManifestArePresent(): void
    {
        $composer = file_get_contents(dirname(__DIR__, 2) . '/composer.json');
        $env = file_get_contents(dirname(__DIR__, 2) . '/.env.example');
        $module = file_get_contents(dirname(__DIR__, 2) . '/config/module.php');
        $auth = file_get_contents(dirname(__DIR__, 2) . '/config/auth.php');
        $manifest = file_get_contents(dirname(__DIR__, 2) . '/modules/Auth/manifest.php');
        $routes = file_get_contents(dirname(__DIR__, 2) . '/modules/Auth/routes/http.php');
        $command = file_get_contents(dirname(__DIR__, 2) . '/modules/Auth/Console/Commands/SeedAuthCommand.php');

        self::assertIsString($composer);
        self::assertIsString($env);
        self::assertIsString($module);
        self::assertIsString($auth);
        self::assertIsString($manifest);
        self::assertIsString($routes);
        self::assertIsString($command);

        self::assertStringContainsString('"App\\\\Modules\\\\": "modules/"', $composer);
        self::assertStringContainsString('AUTH_MODULE_ENABLED=false', $env);
        self::assertStringContainsString("module_path('Auth')", $module);
        self::assertStringContainsString('AUTH_ADMIN_ROLE', $auth);
        self::assertStringContainsString('App\\Modules\\Auth\\AuthServiceProvider', $manifest);
        self::assertStringContainsString("prefix' => 'auth", $routes);
        self::assertStringContainsString("post('/theme'", $routes);
        self::assertStringContainsString("auth:seed", $command);
    }

    public function testAuthModuleViewsAndMigrationsExist(): void
    {
        $files = [
            'modules/Auth/views/layout.twig',
            'modules/Auth/views/login.twig',
            'modules/Auth/views/register.twig',
            'modules/Auth/views/forgot-password.twig',
            'modules/Auth/views/reset-password.twig',
            'modules/Auth/views/change-password.twig',
            'modules/Auth/views/profile.twig',
            'modules/Auth/views/admin/layout.twig',
            'modules/Auth/views/admin/dashboard.twig',
            'modules/Auth/views/admin/users.twig',
            'modules/Auth/views/admin/roles.twig',
            'modules/Auth/views/emails/password-reset.twig',
            'modules/Auth/Database/Migrations/2026_04_04_000001_create_auth_roles_table.php',
            'modules/Auth/Database/Migrations/2026_04_04_000002_create_auth_users_table.php',
            'modules/Auth/Database/Migrations/2026_04_04_000003_create_auth_role_user_table.php',
            'modules/Auth/Database/Migrations/2026_04_04_000004_create_auth_password_resets_table.php',
        ];

        foreach ($files as $file) {
            self::assertFileExists(dirname(__DIR__, 2) . '/' . $file);
        }
    }
}
