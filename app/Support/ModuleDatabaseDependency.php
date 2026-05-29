<?php

declare(strict_types=1);

namespace App\Support;

use Marwa\Framework\Application;

final class ModuleDatabaseDependency
{
    /**
     * Run the callback only when the module does not declare database assets
     * or when the database connection manager is available.
     */
    public static function boot(string $modulePath, Application $app, callable $callback): void
    {
        if (self::requiresDatabase($modulePath) && !$app->has('db')) {
            return;
        }

        $callback();
    }

    public static function requiresDatabase(string $modulePath): bool
    {
        $manifest = self::manifest($modulePath);

        return $manifest['migrations'] !== [] || $manifest['seeders'] !== [];
    }

    /**
     * @return array{migrations:list<string>,seeders:list<string>}
     */
    private static function manifest(string $modulePath): array
    {
        $manifestFile = rtrim($modulePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'manifest.php';

        if (!is_file($manifestFile)) {
            return [
                'migrations' => [],
                'seeders' => [],
            ];
        }

        $manifest = require $manifestFile;

        if (!is_array($manifest)) {
            return [
                'migrations' => [],
                'seeders' => [],
            ];
        }

        return [
            'migrations' => self::stringList($manifest['migrations'] ?? []),
            'seeders' => self::stringList($manifest['seeders'] ?? []),
        ];
    }

    /**
     * @param mixed $values
     * @return list<string>
     */
    private static function stringList(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        return array_values(array_filter(
            array_map(
                static fn (mixed $value): string => trim((string) $value),
                $values
            ),
            static fn (string $value): bool => $value !== ''
        ));
    }
}
