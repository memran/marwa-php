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

        self::assertIsString($template);
        self::assertIsString($layout);
        self::assertStringContainsString('MarwaPHP', $template);
        self::assertStringContainsString('Quick start', $template);
        self::assertStringContainsString('composer install', $template);
        self::assertStringContainsString('switch-theme', $layout);
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
}
