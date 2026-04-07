<?php

declare(strict_types=1);

namespace App\Support;

final class DebugbarCollectors
{
    /**
     * @var list<string>
     */
    private static array $registered = [];

    /**
     * @return list<string>
     */
    public static function defaults(): array
    {
        $configured = env('DEBUGBAR_COLLECTORS', '');

        if (!is_string($configured) || trim($configured) === '') {
            return self::defaultCollectors();
        }

        $overrides = self::normalize(explode(',', $configured));

        return $overrides !== [] ? $overrides : self::defaultCollectors();
    }

    /**
     * Register additional collectors for the current request lifecycle.
     *
     * @param string ...$collectors
     */
    public static function register(string ...$collectors): void
    {
        self::$registered = self::deduplicate(array_merge(self::$registered, self::normalize($collectors)));
    }

    /**
     * Merge registered collectors into a base list.
     *
     * @param list<string> $collectors
     * @return list<string>
     */
    public static function merge(array $collectors = []): array
    {
        return self::deduplicate(array_merge(self::normalize($collectors), self::$registered));
    }

    public static function reset(): void
    {
        self::$registered = [];
    }

    /**
     * @return list<string>
     */
    private static function defaultCollectors(): array
    {
        return [
            'Marwa\\DebugBar\\Collectors\\TimelineCollector',
            'Marwa\\DebugBar\\Collectors\\MemoryCollector',
            'Marwa\\DebugBar\\Collectors\\PhpCollector',
            'Marwa\\DebugBar\\Collectors\\RequestCollector',
            'Marwa\\DebugBar\\Collectors\\SessionCollector',
            'Marwa\\DebugBar\\Collectors\\LogCollector',
            'Marwa\\DebugBar\\Collectors\\ExceptionCollector',
        ];
    }

    /**
     * @param list<string> $collectors
     * @return list<string>
     */
    private static function normalize(array $collectors): array
    {
        $normalized = [];

        foreach ($collectors as $collector) {
            $collector = trim($collector);

            if ($collector === '') {
                continue;
            }

            if (!str_contains($collector, '\\')) {
                $collector = 'Marwa\\DebugBar\\Collectors\\' . $collector;
            }

            $normalized[] = $collector;
        }

        return self::deduplicate($normalized);
    }

    /**
     * @param list<string> $collectors
     * @return list<string>
     */
    private static function deduplicate(array $collectors): array
    {
        return array_values(array_unique($collectors));
    }
}
