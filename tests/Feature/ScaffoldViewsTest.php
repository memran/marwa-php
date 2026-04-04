<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

final class ScaffoldViewsTest extends TestCase
{
    public function testWelcomePageShowsTheStarterMessagingAndThemeSwitching(): void
    {
        $template = file_get_contents(dirname(__DIR__, 2) . '/resources/views/themes/default/views/welcome.twig');
        $layout = file_get_contents(dirname(__DIR__, 2) . '/resources/views/themes/default/views/layout.twig');
        $manifest = file_get_contents(dirname(__DIR__, 2) . '/resources/views/themes/default/manifest.php');

        self::assertIsString($template);
        self::assertIsString($layout);
        self::assertIsString($manifest);
        self::assertStringContainsString('MarwaPHP', $template);
        self::assertStringContainsString('Quick start', $template);
        self::assertStringContainsString('composer install', $template);
        self::assertStringContainsString("'assets_url' => '/assets'", $manifest);
        self::assertStringContainsString('switch-theme', $layout);
        self::assertStringContainsString('theme_asset(', $layout);
        self::assertStringContainsString('csrf', $layout);
    }

    public function testErrorPagesUseTheSharedThemeLayout(): void
    {
        $notFound = file_get_contents(dirname(__DIR__, 2) . '/resources/views/themes/default/views/404.twig');
        $serverError = file_get_contents(dirname(__DIR__, 2) . '/resources/views/themes/default/views/500.twig');

        self::assertIsString($notFound);
        self::assertIsString($serverError);
        self::assertStringContainsString('{% extends "layout.twig" %}', $notFound);
        self::assertStringContainsString('{% extends "layout.twig" %}', $serverError);
        self::assertStringContainsString('Back to home', $notFound);
        self::assertStringContainsString('Reload home', $serverError);
    }

    public function testCompiledStylesheetContainsTheStarterLayoutRules(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2) . '/public/assets/css/app.css');
        $source = file_get_contents(dirname(__DIR__, 2) . '/resources/css/app.css');

        self::assertIsString($css);
        self::assertIsString($source);
        self::assertStringContainsString('tailwindcss v3.4.17', $css);
        self::assertStringContainsString('.auth-page', $css);
        self::assertStringContainsString('.auth-card', $css);
        self::assertStringContainsString('.admin-page', $css);
        self::assertStringContainsString('.admin-sidebar', $css);
        self::assertStringContainsString('.admin-mobile-header', $css);
        self::assertStringContainsString('.admin-mobile-header__crumb', $css);
        self::assertStringContainsString('.admin-mobile-bar', $css);
        self::assertStringContainsString('.admin-mobile-menu__panel', $css);
        self::assertStringContainsString('.admin-user__avatar', $css);
        self::assertStringContainsString('.admin-nav__active', $css);
        self::assertStringContainsString('bg-slate-50', $css);
        self::assertStringContainsString('@tailwind utilities;', $source);
        self::assertStringContainsString('@layer components', $source);
    }

    public function testTailwindToolingIsConfiguredForDevelopmentAndBootstrap(): void
    {
        $packageJson = file_get_contents(dirname(__DIR__, 2) . '/package.json');
        $tailwindConfig = file_get_contents(dirname(__DIR__, 2) . '/tailwind.config.js');
        $bootstrap = file_get_contents(dirname(__DIR__, 2) . '/scripts/post-create-project.php');
        $dockerCompose = file_get_contents(dirname(__DIR__, 2) . '/docker/docker-compose.yml');
        $dockerComposeFpm = file_get_contents(dirname(__DIR__, 2) . '/docker/docker-compose.fpm.yml');

        self::assertIsString($packageJson);
        self::assertIsString($tailwindConfig);
        self::assertIsString($bootstrap);
        self::assertIsString($dockerCompose);
        self::assertIsString($dockerComposeFpm);
        self::assertStringContainsString('"css:dev": "tailwindcss -c tailwind.config.js -i ./resources/css/app.css -o ./public/assets/css/app.css --watch"', $packageJson);
        self::assertStringContainsString('"css:build": "tailwindcss -c tailwind.config.js -i ./resources/css/app.css -o ./public/assets/css/app.css --minify"', $packageJson);
        self::assertStringContainsString("'./modules/**/*.twig'", $tailwindConfig);
        self::assertStringContainsString('npm ci', $bootstrap);
        self::assertStringContainsString('npm run build', $bootstrap);
        self::assertStringContainsString('profiles:', $dockerCompose);
        self::assertStringContainsString('assets', $dockerCompose);
        self::assertStringContainsString('npm run dev', $dockerComposeFpm);
    }

    public function testAuthModuleUsesTheLightAdminShell(): void
    {
        $authLayout = file_get_contents(dirname(__DIR__, 2) . '/modules/Auth/views/layout.twig');
        $adminLayout = file_get_contents(dirname(__DIR__, 2) . '/modules/Auth/views/admin/layout.twig');
        $changePassword = file_get_contents(dirname(__DIR__, 2) . '/modules/Auth/views/change-password.twig');
        $profile = file_get_contents(dirname(__DIR__, 2) . '/modules/Auth/views/profile.twig');

        self::assertIsString($authLayout);
        self::assertIsString($adminLayout);
        self::assertIsString($changePassword);
        self::assertIsString($profile);
        self::assertStringContainsString('light, polished auth experience', $authLayout);
        self::assertStringContainsString('admin-shell', $adminLayout);
        self::assertStringContainsString('admin-sidebar', $adminLayout);
        self::assertStringContainsString('admin-mobile-header', $adminLayout);
        self::assertStringContainsString('Admin /', $adminLayout);
        self::assertStringContainsString('admin-mobile-bar', $adminLayout);
        self::assertStringContainsString('admin-mobile-menu', $adminLayout);
        self::assertStringContainsString('admin-user', $adminLayout);
        self::assertStringContainsString('active_nav', $adminLayout);
        self::assertStringContainsString('action="/admin/theme"', $adminLayout);
        self::assertStringContainsString("extends '@auth/admin/layout.twig'", $changePassword);
        self::assertStringContainsString('Remembered access', $profile);
    }
}
