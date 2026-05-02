<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SharedJobMigrationTest extends TestCase
{
    public function test_shared_queue_and_schedule_migrations_exist_in_database_migrations(): void
    {
        $migrations = glob(dirname(__DIR__, 2) . '/database/migrations/*_create_*_table.php') ?: [];

        $contents = [];
        foreach ($migrations as $migration) {
            $contents[$migration] = file_get_contents($migration) ?: '';
        }

        self::assertNotEmpty($migrations);
        self::assertNotEmpty(array_filter($contents, static fn (string $content): bool => str_contains($content, 'queue_jobs')));
        self::assertNotEmpty(array_filter($contents, static fn (string $content): bool => str_contains($content, 'schedule_jobs')));
    }
}
