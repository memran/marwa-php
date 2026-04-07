<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

final class AdminThemeScaffoldTest extends TestCase
{
    public function testAdminThemeThemeScaffoldIsPresent(): void
    {
        $basePath = dirname(__DIR__, 2);

        $routes = file_get_contents($basePath . '/routes/web.php');
        $controller = file_get_contents($basePath . '/app/Controllers/AdminController.php');
        $frontendController = file_get_contents($basePath . '/app/Controllers/FrontendController.php');
        $backendController = file_get_contents($basePath . '/app/Controllers/BackendController.php');
        $manifest = file_get_contents($basePath . '/resources/views/themes/admin/manifest.php');
        $layout = file_get_contents($basePath . '/resources/views/themes/admin/views/layout.twig');
        $adminLayout = file_get_contents($basePath . '/resources/views/themes/admin/views/admin/layout.twig');
        $dashboard = file_get_contents($basePath . '/resources/views/themes/admin/views/home/index.twig');

        self::assertIsString($routes);
        self::assertIsString($controller);
        self::assertIsString($frontendController);
        self::assertIsString($backendController);
        self::assertIsString($manifest);
        self::assertIsString($layout);
        self::assertIsString($adminLayout);
        self::assertIsString($dashboard);
        self::assertStringContainsString("use App\Controllers\AdminController;", $routes);
        self::assertStringContainsString("Router::get('/admin'", $routes);
        self::assertStringContainsString('abstract class FrontendController', $frontendController);
        self::assertStringContainsString('abstract class BackendController', $backendController);
        self::assertStringContainsString('extends BackendController', $controller);
        self::assertStringContainsString('renderFrontend(', $frontendController);
        self::assertStringContainsString('renderBackend(', $backendController);
        self::assertStringContainsString('renderBackend(', $controller);
        self::assertStringContainsString("'name' => 'admin'", $manifest);
        self::assertStringContainsString("'parent' => 'default'", $manifest);
        self::assertStringContainsString('Admin Theme', $layout);
        self::assertStringContainsString('admin-shell', $adminLayout);
        self::assertStringContainsString('method="get" action="/admin"', $adminLayout);
        self::assertStringContainsString('name="theme"', $adminLayout);
        self::assertStringContainsString('name="preview"', $adminLayout);
        self::assertStringContainsString('Admin workspace', $dashboard);
        self::assertStringContainsString("{% extends 'admin/layout.twig' %}", $dashboard);
    }
}
