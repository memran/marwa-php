<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Console\Commands\ThemeMakeCommand;
use App\Theme\ThemeScaffolder;
use App\Theme\ThemeValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ThemeMakeCommandTest extends TestCase
{
    private string $basePath;
    private string $themesPath;
    private string $publicThemesPath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-theme-make-' . bin2hex(random_bytes(6));
        $this->themesPath = $this->basePath . '/resources/views/themes';
        $this->publicThemesPath = $this->basePath . '/public/themes';

        $this->copyDirectory(__DIR__ . '/../../resources/views/themes/admin', $this->themesPath . '/admin');
        $this->copyDirectory(__DIR__ . '/../../public/themes/admin', $this->publicThemesPath . '/admin');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);
        parent::tearDown();
    }

    public function testItScaffoldsAThemeFromTheAdminTemplate(): void
    {
        $command = new ThemeMakeCommand(
            new ThemeScaffolder($this->themesPath, $this->publicThemesPath),
            new ThemeValidator($this->themesPath)
        );

        $tester = new CommandTester($command);
        $exitCode = $tester->execute(['theme' => 'admin-modern']);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Created theme: admin-modern', $tester->getDisplay());
        self::assertStringContainsString('Theme "admin-modern" is ready.', $tester->getDisplay());
        self::assertFileExists($this->themesPath . '/admin-modern/manifest.php');
        self::assertFileExists($this->publicThemesPath . '/admin-modern/assets/js/dashboard.js');

        $manifest = require $this->themesPath . '/admin-modern/manifest.php';
        $dashboardJs = file_get_contents($this->publicThemesPath . '/admin-modern/assets/js/dashboard.js');

        self::assertIsArray($manifest);
        self::assertSame('admin-modern', $manifest['name']);
        self::assertSame('admin-modern', $manifest['slug']);
        self::assertSame('/themes/admin-modern', $manifest['assets_url']);
        self::assertIsString($dashboardJs);
        self::assertStringContainsString('/themes/admin-modern/assets/icons/lucide.svg', $dashboardJs);
    }

    public function testItRejectsAnInvalidThemeName(): void
    {
        $command = new ThemeMakeCommand(
            new ThemeScaffolder($this->themesPath, $this->publicThemesPath),
            new ThemeValidator($this->themesPath)
        );

        $tester = new CommandTester($command);
        $exitCode = $tester->execute(['theme' => 'Admin Modern']);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertStringContainsString('Theme name must use lowercase letters, numbers, and hyphens only.', $tester->getDisplay());
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
