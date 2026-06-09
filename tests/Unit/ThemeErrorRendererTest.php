<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Errors\ThemeErrorRenderer;
use Marwa\Framework\Application;
use Marwa\Framework\Bootstrappers\AppBootstrapper;
use Marwa\Framework\Facades\View;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ThemeErrorRendererTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-error-renderer-' . bin2hex(random_bytes(6));

        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . '/config', 0777, true);
        mkdir($this->basePath . '/resources/views/themes/default/views/errors', 0777, true);
        mkdir($this->basePath . '/resources/views/themes/admin/views/errors', 0777, true);

        copy(__DIR__ . '/../../config/view.php', $this->basePath . '/config/view.php');
        copy(__DIR__ . '/../../config/security.php', $this->basePath . '/config/security.php');
        copy(__DIR__ . '/../../config/error.php', $this->basePath . '/config/error.php');

        $this->copyDirectory(__DIR__ . '/../../resources/views/themes/default', $this->basePath . '/resources/views/themes/default');
        $this->copyDirectory(__DIR__ . '/../../resources/views/themes/admin', $this->basePath . '/resources/views/themes/admin');

        file_put_contents(
            $this->basePath . '/.env',
            "APP_ENV=production\nAPP_NAME=\"Marwa Starter\"\nAPP_KEY=0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef\nFRONTEND_THEME=default\nADMIN_THEME=admin\nTIMEZONE=UTC\n"
        );
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['marwa_app']);
        $this->removeDirectory($this->basePath);

        parent::tearDown();
    }

    public function testRendersThemed500ForFrontendAndAdminThemes(): void
    {
        $app = new Application($this->basePath);
        $GLOBALS['marwa_app'] = $app;
        $app->make(AppBootstrapper::class)->bootstrap();

        $renderer = new ThemeErrorRenderer();
        $exception = new RuntimeException('Database connection failed');

        View::theme('default');
        ob_start();
        $renderer->renderException($exception, 'Marwa Starter', false);
        $defaultHtml = (string) ob_get_clean();

        View::theme('admin');
        ob_start();
        $renderer->renderException($exception, 'Marwa Starter', false);
        $adminHtml = (string) ob_get_clean();

        self::assertStringContainsString('Something broke on our side.', $defaultHtml);
        self::assertStringContainsString('Marwa Framework starter', $defaultHtml);
        self::assertStringContainsString('Reference ID', $defaultHtml);
        self::assertStringContainsString('Something broke on our side.', $adminHtml);
        self::assertStringContainsString('Admin console', $adminHtml);
        self::assertStringContainsString('Reference ID', $adminHtml);
    }

    private function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0777, true);
        }

        $items = scandir($source);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $from = $source . DIRECTORY_SEPARATOR . $item;
            $to = $destination . DIRECTORY_SEPARATOR . $item;

            if (is_dir($from)) {
                $this->copyDirectory($from, $to);
                continue;
            }

            copy($from, $to);
        }
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);

        if ($items !== false) {
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }

                $fullPath = $path . DIRECTORY_SEPARATOR . $item;

                if (is_dir($fullPath)) {
                    $this->removeDirectory($fullPath);
                    continue;
                }

                @unlink($fullPath);
            }
        }

        @rmdir($path);
    }
}
