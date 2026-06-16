<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Console\Commands\ThemeValidateCommand;
use App\Theme\ThemeValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ThemeValidatorTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-theme-' . bin2hex(random_bytes(6));
        $this->ensureDirectory($this->basePath . '/admin/layouts');
        $this->ensureDirectory($this->basePath . '/admin/partials');
        $this->ensureDirectory($this->basePath . '/admin/components');
        $this->ensureDirectory($this->basePath . '/admin/assets/css');
        $this->ensureDirectory($this->basePath . '/admin/assets/js');
        $this->ensureDirectory($this->basePath . '/admin/assets/images');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);
        parent::tearDown();
    }

    public function testValidThemePasses(): void
    {
        $this->createValidTheme();

        $result = (new ThemeValidator($this->basePath))->validate('admin');

        self::assertTrue($result->isValid());
        self::assertSame('Admin Default', $result->displayName());
    }

    public function testMissingManifestFails(): void
    {
        $this->createThemeFiles();

        $result = (new ThemeValidator($this->basePath))->validate('admin');

        self::assertFalse($result->isValid());
        self::assertContains('Missing manifest.php', $result->errors());
    }

    public function testMissingRequiredLayoutFails(): void
    {
        $this->createThemeFiles();
        $this->writeManifest();
        @unlink($this->basePath . '/admin/layouts/auth.twig');

        $result = (new ThemeValidator($this->basePath))->validate('admin');

        self::assertFalse($result->isValid());
        self::assertContains('Missing required layout: layouts/auth.twig', $result->errors());
    }

    public function testMissingRequiredComponentFails(): void
    {
        $this->createThemeFiles();
        $this->writeManifest();
        @unlink($this->basePath . '/admin/components/button.twig');

        $result = (new ThemeValidator($this->basePath))->validate('admin');

        self::assertFalse($result->isValid());
        self::assertContains('Missing required component: components/button.twig', $result->errors());
    }

    public function testDeclaredMissingAssetFails(): void
    {
        $this->createThemeFiles();
        $this->writeManifest();
        @unlink($this->basePath . '/admin/assets/css/layout.css');

        $result = (new ThemeValidator($this->basePath))->validate('admin');

        self::assertFalse($result->isValid());
        self::assertContains('Declared asset not found: css/layout.css', $result->errors());
    }

    public function testSlugMismatchFails(): void
    {
        $themePath = $this->basePath . '/admin';
        $this->ensureDirectory($themePath . '/layouts');
        $this->ensureDirectory($themePath . '/partials');
        $this->ensureDirectory($themePath . '/components');
        $this->ensureDirectory($themePath . '/assets/css');
        $this->ensureDirectory($themePath . '/assets/js');
        $this->ensureDirectory($themePath . '/assets/images');

        $this->writeFile($themePath . '/manifest.php', <<<'PHP'
<?php

declare(strict_types=1);

return [
    'name' => 'admin',
    'slug' => 'admin-default',
    'version' => '1.0.0',
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
            $this->writeFile($themePath . '/' . $relativePath, 'test');
        }

        $result = (new ThemeValidator($this->basePath))->validate('admin');

        self::assertFalse($result->isValid());
        self::assertContains('Theme slug must match folder name: expected "admin", got "admin-default"', $result->errors());
    }

    public function testCommandOutputsValidationResult(): void
    {
        $this->createValidTheme();

        $command = new ThemeValidateCommand(new ThemeValidator($this->basePath));
        $tester = new CommandTester($command);
        $exitCode = $tester->execute(['theme' => 'admin']);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Validating theme:', $tester->getDisplay());
        self::assertStringContainsString('Theme "admin" is valid.', $tester->getDisplay());
    }

    private function createValidTheme(): void
    {
        $this->createThemeFiles();
        $this->writeManifest();
    }

    private function createThemeFiles(): void
    {
        $themePath = $this->basePath . '/admin';

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
            $this->writeFile($themePath . '/' . $relativePath, 'test');
        }

        $this->ensureDirectory($themePath . '/assets/images');
    }

    private function writeManifest(): void
    {
        $this->writeFile($this->basePath . '/admin/manifest.php', <<<'PHP'
<?php

declare(strict_types=1);

return [
    'name' => 'admin',
    'slug' => 'admin',
    'version' => '1.0.0',
    'meta' => [
        'label' => 'Admin Default',
    ],
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
    }

    private function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        mkdir($path, 0777, true);
    }

    private function writeFile(string $path, string $content): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($path, $content);
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($directory);
    }
}
