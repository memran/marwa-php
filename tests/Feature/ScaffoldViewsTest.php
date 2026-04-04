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

        self::assertIsString($css);
        self::assertStringContainsString('main > section:first-of-type', $css);
        self::assertStringContainsString('--bg:', $css);
        self::assertStringContainsString('#start pre', $css);
    }
}
