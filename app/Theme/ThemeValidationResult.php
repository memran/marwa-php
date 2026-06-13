<?php

declare(strict_types=1);

namespace App\Theme;

final class ThemeValidationResult
{
    /**
     * @var array<string, bool>
     */
    private array $checks = [
        'manifest' => false,
        'layouts' => false,
        'partials' => false,
        'components' => false,
        'assets' => false,
    ];

    /**
     * @var list<string>
     */
    private array $errors = [];

    /**
     * @var array<string, mixed>|null
     */
    private ?array $manifest = null;

    private string $displayName;

    public function __construct(
        private readonly string $themeName,
        private readonly string $themePath,
        private readonly string $manifestPath
    ) {
        $this->displayName = $themeName;
    }

    public function themeName(): string
    {
        return $this->themeName;
    }

    public function themePath(): string
    {
        return $this->themePath;
    }

    public function manifestPath(): string
    {
        return $this->manifestPath;
    }

    public function displayName(): string
    {
        return $this->displayName;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function manifest(): ?array
    {
        return $this->manifest;
    }

    public function hasManifest(): bool
    {
        return $this->checks['manifest'];
    }

    public function hasLayouts(): bool
    {
        return $this->checks['layouts'];
    }

    public function hasPartials(): bool
    {
        return $this->checks['partials'];
    }

    public function hasComponents(): bool
    {
        return $this->checks['components'];
    }

    public function hasAssets(): bool
    {
        return $this->checks['assets'];
    }

    /**
     * @param array<string, mixed> $manifest
     */
    public function setManifest(array $manifest): void
    {
        $this->manifest = $manifest;
        $this->checks['manifest'] = true;

        $meta = is_array($manifest['meta'] ?? null) ? $manifest['meta'] : [];
        $label = $this->normalizeString($meta['label'] ?? $manifest['name'] ?? null);
        if ($label !== null) {
            $this->displayName = $label;
        }
    }

    public function markCheck(string $name, bool $passed): void
    {
        if (!array_key_exists($name, $this->checks)) {
            $this->checks[$name] = $passed;

            return;
        }

        $this->checks[$name] = $passed;
    }

    public function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    /**
     * @return list<string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    private function normalizeString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
