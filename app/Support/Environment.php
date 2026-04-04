<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class Environment
{
    public static function string(string $key, ?string $default = null): string
    {
        $value = self::read($key);

        if ($value === null || $value === '') {
            return $default ?? '';
        }

        return $value;
    }

    public static function requiredString(string $key): string
    {
        $value = self::string($key);

        if ($value === '') {
            throw new RuntimeException(sprintf('Missing required environment variable "%s".', $key));
        }

        return $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::read($key);

        if ($value === null || $value === '') {
            return $default;
        }

        $filtered = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $filtered ?? $default;
    }

    public static function integer(string $key, int $default = 0): int
    {
        $value = self::read($key);

        if ($value === null || $value === '') {
            return $default;
        }

        if (!is_numeric($value)) {
            throw new RuntimeException(sprintf('Environment variable "%s" must be numeric.', $key));
        }

        return (int) $value;
    }

    private static function read(string $key): ?string
    {
        $value = getenv($key);

        if ($value === false) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? '' : $value;
    }
}
