<?php

declare(strict_types=1);

namespace App\Theme;

final class ThemeValidator
{
    /**
     * @var list<string>
     */
    private const REQUIRED_LAYOUTS = [
        'layouts/admin.twig',
        'layouts/auth.twig',
        'layouts/blank.twig',
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_PARTIALS = [
        'partials/head.twig',
        'partials/header.twig',
        'partials/sidebar.twig',
        'partials/footer.twig',
        'partials/scripts.twig',
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_COMPONENTS = [
        'components/button.twig',
        'components/card.twig',
        'components/alert.twig',
        'components/input.twig',
        'components/select.twig',
        'components/table.twig',
        'components/breadcrumb.twig',
        'components/dashboard-widgets.twig',
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_ASSETS = [
        'assets/css/variables.css',
        'assets/css/layout.css',
        'assets/css/components.css',
        'assets/js/theme.js',
    ];

    public function __construct(
        private readonly ?string $themesBasePath = null
    ) {
    }

    public function validate(string $themeName): ThemeValidationResult
    {
        $themeName = trim($themeName);

        if ($themeName === '') {
            throw new \InvalidArgumentException('Theme name cannot be empty.');
        }

        $themesBasePath = $this->themesBasePath();
        $themePath = $themesBasePath . DIRECTORY_SEPARATOR . $themeName;
        $manifestPath = $themePath . DIRECTORY_SEPARATOR . 'manifest.php';
        $result = new ThemeValidationResult($themeName, $themePath, $manifestPath);

        if (!is_dir($themePath)) {
            $result->addError(sprintf('Missing theme directory: %s', $themePath));

            return $result;
        }

        $this->validateManifest($result, $manifestPath, $themeName);
        $this->validateRequiredFiles($result, $themePath, self::REQUIRED_LAYOUTS, 'layout');
        $this->validateRequiredFiles($result, $themePath, self::REQUIRED_PARTIALS, 'partial');
        $this->validateRequiredFiles($result, $themePath, self::REQUIRED_COMPONENTS, 'component');
        $this->validateRequiredAssets($result, $themePath, self::REQUIRED_ASSETS);
        $this->validateImagesDirectory($result, $themePath);

        return $result;
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

    private function validateManifest(ThemeValidationResult $result, string $manifestPath, string $folderName): void
    {
        if (!is_file($manifestPath)) {
            $result->addError('Missing manifest.php');

            return;
        }

        try {
            $manifest = require $manifestPath;
        } catch (\Throwable $throwable) {
            $result->addError(sprintf('Unable to load manifest.php: %s', $throwable->getMessage()));

            return;
        }

        if (!is_array($manifest)) {
            $result->addError('manifest.php must return an array');

            return;
        }

        $result->setManifest($manifest);

        foreach (['name', 'slug', 'version'] as $key) {
            if (!isset($manifest[$key]) || !is_string($manifest[$key]) || trim($manifest[$key]) === '') {
                $result->addError(sprintf('Missing required manifest key: %s', $key));
            }
        }

        $slug = is_string($manifest['slug'] ?? null) ? trim($manifest['slug']) : '';
        if ($slug !== '' && $slug !== $folderName) {
            $result->addError(sprintf('Theme slug must match folder name: expected "%s", got "%s"', $folderName, $slug));
        }

        $layouts = $manifest['layouts'] ?? null;
        if (!is_array($layouts)) {
            $result->addError('Manifest layouts must be an array');
        }

        $assets = $manifest['assets'] ?? null;
        if (!is_array($assets)) {
            $result->addError('Manifest assets must be an array');
        }

        if (is_array($assets)) {
            $declaredAssets = array_merge(
                array_values(array_filter($assets['css'] ?? [], 'is_string')),
                array_values(array_filter($assets['js'] ?? [], 'is_string'))
            );

            foreach ($declaredAssets as $asset) {
                $assetPath = $result->themePath() . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $asset), DIRECTORY_SEPARATOR);
                if (!is_file($assetPath)) {
                    $result->addError(sprintf('Declared asset not found: %s', $asset));
                }
            }
        }
    }

    /**
     * @param list<string> $paths
     */
    private function validateRequiredFiles(ThemeValidationResult $result, string $themePath, array $paths, string $type): void
    {
        $missing = [];

        foreach ($paths as $relativePath) {
            $absolutePath = $themePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            if (!is_file($absolutePath)) {
                $missing[] = $relativePath;
                $result->addError(sprintf('Missing required %s: %s', $type, $relativePath));
            }
        }

        $result->markCheck($type . 's', $missing === []);
    }

    /**
     * @param list<string> $paths
     */
    private function validateRequiredAssets(ThemeValidationResult $result, string $themePath, array $paths): void
    {
        $missing = [];

        foreach ($paths as $relativePath) {
            $absolutePath = $themePath . DIRECTORY_SEPARATOR . $relativePath;
            if (!is_file($absolutePath)) {
                $missing[] = $relativePath;
                $result->addError(sprintf('Missing required asset: %s', $relativePath));
            }
        }

        $result->markCheck('assets', $missing === []);
    }

    private function validateImagesDirectory(ThemeValidationResult $result, string $themePath): void
    {
        $imagesPath = $themePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images';

        if (!is_dir($imagesPath)) {
            $result->addError('Missing required directory: assets/images');
        }
    }
}
