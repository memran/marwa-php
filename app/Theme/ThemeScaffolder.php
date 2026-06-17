<?php

declare(strict_types=1);

namespace App\Theme;

use InvalidArgumentException;
use RuntimeException;

final class ThemeScaffolder
{
    private const TEMPLATE_THEME = 'admin';

    public function __construct(
        private readonly ?string $themesBasePath = null,
        private readonly ?string $publicThemesBasePath = null
    ) {
    }

    public function scaffold(string $themeName): ThemeScaffoldResult
    {
        $themeName = $this->normalizeThemeName($themeName);

        $sourceThemePath = $this->themesBasePath() . DIRECTORY_SEPARATOR . self::TEMPLATE_THEME;
        $sourcePublicThemePath = $this->publicThemesBasePath() . DIRECTORY_SEPARATOR . self::TEMPLATE_THEME;
        $targetThemePath = $this->themesBasePath() . DIRECTORY_SEPARATOR . $themeName;
        $targetPublicThemePath = $this->publicThemesBasePath() . DIRECTORY_SEPARATOR . $themeName;

        if (!is_dir($sourceThemePath)) {
            throw new RuntimeException(sprintf('Source theme not found: %s', $sourceThemePath));
        }

        if (!is_dir($sourcePublicThemePath)) {
            throw new RuntimeException(sprintf('Source public theme not found: %s', $sourcePublicThemePath));
        }

        if (is_dir($targetThemePath)) {
            throw new RuntimeException(sprintf('Theme directory already exists: %s', $targetThemePath));
        }

        if (is_dir($targetPublicThemePath)) {
            throw new RuntimeException(sprintf('Public theme directory already exists: %s', $targetPublicThemePath));
        }

        $this->copyDirectory($sourceThemePath, $targetThemePath);
        $this->copyDirectory($sourcePublicThemePath, $targetPublicThemePath);
        $this->rewriteThemeFiles($themeName, $targetThemePath, $targetPublicThemePath);

        return new ThemeScaffoldResult($themeName, $targetThemePath, $targetPublicThemePath);
    }

    private function normalizeThemeName(string $themeName): string
    {
        $themeName = strtolower(trim($themeName));

        if ($themeName === '') {
            throw new InvalidArgumentException('Theme name cannot be empty.');
        }

        if (preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $themeName) !== 1) {
            throw new InvalidArgumentException('Theme name must use lowercase letters, numbers, and hyphens only.');
        }

        return $themeName;
    }

    private function themesBasePath(): string
    {
        if ($this->themesBasePath !== null && trim($this->themesBasePath) !== '') {
            return rtrim($this->themesBasePath, DIRECTORY_SEPARATOR);
        }

        try {
            $configuredPath = config('view.themePath');

            if (is_string($configuredPath) && trim($configuredPath) !== '') {
                return rtrim($configuredPath, DIRECTORY_SEPARATOR);
            }
        } catch (\Throwable) {
        }

        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'themes';
    }

    private function publicThemesBasePath(): string
    {
        if ($this->publicThemesBasePath !== null && trim($this->publicThemesBasePath) !== '') {
            return rtrim($this->publicThemesBasePath, DIRECTORY_SEPARATOR);
        }

        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'themes';
    }

    private function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination) && !mkdir($destination, 0777, true) && !is_dir($destination)) {
            throw new RuntimeException(sprintf('Unable to create directory: %s', $destination));
        }

        $items = scandir($source);

        if ($items === false) {
            throw new RuntimeException(sprintf('Unable to read directory: %s', $source));
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

            if (!copy($from, $to)) {
                throw new RuntimeException(sprintf('Unable to copy file: %s', $from));
            }
        }
    }

    private function rewriteThemeFiles(string $themeName, string $themePath, string $publicThemePath): void
    {
        $this->rewriteFile($themePath . DIRECTORY_SEPARATOR . 'manifest.php', function (string $content) use ($themeName): string {
            $content = str_replace("'name' => 'admin'", sprintf("'name' => '%s'", $themeName), $content);
            $content = str_replace("'slug' => 'admin'", sprintf("'slug' => '%s'", $themeName), $content);
            $content = str_replace("'label' => 'Admin Default'", sprintf("'label' => '%s'", $this->displayLabel($themeName)), $content);
            $content = str_replace("'description' => 'Minimal admin theme package for Marwa PHP.'", sprintf("'description' => '%s'", $this->manifestDescription($themeName)), $content);

            return str_replace("'assets_url' => '/themes/admin'", sprintf("'assets_url' => '/themes/%s'", $themeName), $content);
        });

        $this->rewriteDirectory($themePath, fn (string $content): string => str_replace('/themes/admin/', sprintf('/themes/%s/', $themeName), $content));
        $this->rewriteDirectory($publicThemePath, fn (string $content): string => str_replace('/themes/admin/', sprintf('/themes/%s/', $themeName), $content));
    }

    /**
     * @param callable(string): string $rewriter
     */
    private function rewriteDirectory(string $path, callable $rewriter): void
    {
        $items = scandir($path);

        if ($items === false) {
            throw new RuntimeException(sprintf('Unable to read directory: %s', $path));
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($fullPath)) {
                $this->rewriteDirectory($fullPath, $rewriter);

                continue;
            }

            $content = file_get_contents($fullPath);

            if ($content === false) {
                throw new RuntimeException(sprintf('Unable to read file: %s', $fullPath));
            }

            $updated = $rewriter($content);

            if ($updated !== $content && file_put_contents($fullPath, $updated) === false) {
                throw new RuntimeException(sprintf('Unable to rewrite file: %s', $fullPath));
            }
        }
    }

    /**
     * @param callable(string): string $rewriter
     */
    private function rewriteFile(string $path, callable $rewriter): void
    {
        $content = file_get_contents($path);

        if ($content === false) {
            throw new RuntimeException(sprintf('Unable to read file: %s', $path));
        }

        $updated = $rewriter($content);

        if (file_put_contents($path, $updated) === false) {
            throw new RuntimeException(sprintf('Unable to write file: %s', $path));
        }
    }

    private function displayLabel(string $themeName): string
    {
        return implode(' ', array_map('ucfirst', explode('-', $themeName)));
    }

    private function manifestDescription(string $themeName): string
    {
        return sprintf('%s admin theme package for Marwa PHP.', $this->displayLabel($themeName));
    }
}
