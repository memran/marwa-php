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
        $themeCss = file_get_contents($basePath . '/resources/views/themes/admin/assets/css/app.css');
        $themeLogo = file_get_contents($basePath . '/resources/views/themes/admin/assets/images/logo-admin.svg');
        $publicThemeCss = file_get_contents($basePath . '/public/themes/admin/assets/css/app.css');
        $publicThemeLogo = file_get_contents($basePath . '/public/themes/admin/assets/images/logo-admin.svg');
        $layout = file_get_contents($basePath . '/resources/views/themes/admin/views/layout.twig');
        $adminLayout = file_get_contents($basePath . '/resources/views/themes/admin/views/admin/layout.twig');
        $dashboard = file_get_contents($basePath . '/resources/views/themes/admin/views/home/index.twig');

        self::assertIsString($routes);
        self::assertIsString($controller);
        self::assertIsString($frontendController);
        self::assertIsString($backendController);
        self::assertIsString($manifest);
        self::assertIsString($themeCss);
        self::assertIsString($themeLogo);
        self::assertIsString($publicThemeCss);
        self::assertIsString($publicThemeLogo);
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
        self::assertStringContainsString("'assets_url' => '/themes/admin/assets'", $manifest);
        self::assertStringContainsString('Admin Starter', $layout);
        self::assertStringContainsString('admin-page theme-admin', $layout);
        self::assertStringContainsString('@import url(\'/assets/css/app.css\')', $themeCss);
        self::assertStringContainsString('@import url(\'/assets/css/app.css\')', $publicThemeCss);
        self::assertStringContainsString('Marwa Admin', $themeLogo);
        self::assertStringContainsString('Marwa Admin', $publicThemeLogo);
        self::assertStringContainsString('Theme preview', $layout);
        self::assertStringContainsString('admin-shell', $adminLayout);
        self::assertStringContainsString('Backend workspace', $adminLayout);
        self::assertStringContainsString('method="get" action="/admin"', $layout);
        self::assertStringContainsString('name="theme"', $layout);
        self::assertStringContainsString('name="preview"', $layout);
        self::assertStringContainsString('Starter complete theme', $dashboard);
        self::assertStringContainsString('theme_asset(\'images/logo-admin.svg\')', $layout);
        self::assertStringContainsString("{% extends 'admin/layout.twig' %}", $dashboard);
    }
}
