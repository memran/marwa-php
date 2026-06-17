<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Console\Commands\ThemePublishCommand;
use App\Theme\AdminThemePersistence;
use App\Theme\ThemePublisher;
use App\Theme\ThemeValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ThemePublishCommandTest extends TestCase
{
    private string $basePath;
    private string $themesPath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-theme-publish-' . bin2hex(random_bytes(6));
        $this->themesPath = $this->basePath . '/resources/views/themes';

        mkdir($this->themesPath . '/admin-modern/layouts', 0777, true);
        mkdir($this->themesPath . '/admin-modern/partials', 0777, true);
        mkdir($this->themesPath . '/admin-modern/components', 0777, true);
        mkdir($this->themesPath . '/admin-modern/assets/css', 0777, true);
        mkdir($this->themesPath . '/admin-modern/assets/js', 0777, true);
        mkdir($this->themesPath . '/admin-modern/assets/images', 0777, true);

        $this->writeTheme('admin-modern', 'admin');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);
        parent::tearDown();
    }

    public function testItPublishesAnAdminThemeThroughSettingsPersistence(): void
    {
        $persistence = new class implements AdminThemePersistence
        {
            /** @var list<string> */
            public array $publishedThemes = [];

            public function publish(string $themeName): void
            {
                $this->publishedThemes[] = $themeName;
            }
        };

        $command = new ThemePublishCommand(
            new ThemePublisher(new ThemeValidator($this->themesPath), $persistence)
        );

        $tester = new CommandTester($command);
        $exitCode = $tester->execute(['theme' => 'admin-modern']);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Published theme: admin-modern', $tester->getDisplay());
        self::assertStringContainsString('Published through: database', $tester->getDisplay());
        self::assertStringContainsString('Admin theme is now set to "admin-modern".', $tester->getDisplay());
        self::assertSame(['admin-modern'], $persistence->publishedThemes);
    }

    public function testItRejectsNonAdminThemes(): void
    {
        mkdir($this->themesPath . '/frontend/layouts', 0777, true);
        mkdir($this->themesPath . '/frontend/partials', 0777, true);
        mkdir($this->themesPath . '/frontend/components', 0777, true);
        mkdir($this->themesPath . '/frontend/assets/css', 0777, true);
        mkdir($this->themesPath . '/frontend/assets/js', 0777, true);
        mkdir($this->themesPath . '/frontend/assets/images', 0777, true);
        $this->writeTheme('frontend', 'frontend');

        $command = new ThemePublishCommand(
            new ThemePublisher(new ThemeValidator($this->themesPath), new class implements AdminThemePersistence
            {
                public function publish(string $themeName): void
                {
                }
            })
        );

        $tester = new CommandTester($command);
        $exitCode = $tester->execute(['theme' => 'frontend']);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('Theme "frontend" is not an admin theme.', $tester->getDisplay());
    }

    private function writeTheme(string $themeName, string $type): void
    {
        $themePath = $this->themesPath . '/' . $themeName;

        file_put_contents($themePath . '/manifest.php', <<<PHP
<?php

declare(strict_types=1);

return [
    'name' => '{$themeName}',
    'slug' => '{$themeName}',
    'version' => '1.0.0',
    'type' => '{$type}',
    'layouts' => [
        'admin' => 'layouts/admin.twig',
        'auth' => 'layouts/auth.twig',
        'blank' => 'layouts/blank.twig',
    ],
    'assets' => [
        'css' => [
            'css/variables.css',
            'css/layout.css',
            'css/components.css',
        ],
        'js' => [
            'js/theme.js',
        ],
    ],
];
PHP);

        foreach ([
            'layouts/admin.twig',
            'layouts/auth.twig',
            'layouts/blank.twig',
            'partials/head.twig',
            'partials/header.twig',
            'partials/sidebar.twig',
            'partials/footer.twig',
            'partials/scripts.twig',
            'components/button.twig',
            'components/card.twig',
            'components/alert.twig',
            'components/input.twig',
            'components/select.twig',
            'components/table.twig',
            'components/breadcrumb.twig',
            'components/dashboard-widgets.twig',
            'assets/css/variables.css',
            'assets/css/layout.css',
            'assets/css/components.css',
            'assets/js/theme.js',
        ] as $relativePath) {
            $target = $themePath . '/' . $relativePath;
            $dir = dirname($target);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($target, 'test');
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
