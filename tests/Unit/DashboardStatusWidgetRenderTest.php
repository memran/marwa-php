<?php

declare(strict_types=1);

namespace Tests\Unit;

use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\Supports\Runtime;
use Marwa\Framework\Views\View;
use PHPUnit\Framework\TestCase;

final class DashboardStatusWidgetRenderTest extends TestCase
{
    public function testItRendersDashboardStatusWidgetsFromTheRegisteredNamespace(): void
    {
        Runtime::setConsoleOverride(false);
        try {
            $app = new Application(getcwd());
            $app->make(AppBootstrapper::class)->bootstrap();

            /** @var View $view */
            $view = app(View::class);
            $view->theme('admin');

            $html = $view->render('@dashboard_status/widgets/app_status', [
                'card' => [
                    'label' => 'Application',
                    'value' => 'MarwaPHP',
                    'status' => 'Healthy',
                ],
            ]);

            self::assertStringContainsString('Application', $html);
            self::assertStringContainsString('MarwaPHP', $html);
            self::assertStringContainsString('Healthy', $html);
        } finally {
            Runtime::setConsoleOverride(null);
        }
    }
}
