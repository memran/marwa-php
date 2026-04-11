<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Commands\CheckDatabaseConnectivityCommand;
use Marwa\DB\Config\Config as DbConfig;
use Marwa\DB\Connection\ConnectionManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class CheckDatabaseConnectivityCommandTest extends TestCase
{
    public function testItPrintsSuccessfulDatabaseConnectivity(): void
    {
        $databasePath = tempnam(sys_get_temp_dir(), 'marwa-db-');
        self::assertNotFalse($databasePath);

        $manager = new ConnectionManager(new DbConfig([
            'default' => 'sqlite',
            'connections' => [
                'sqlite' => [
                    'driver' => 'sqlite',
                    'database' => $databasePath,
                ],
            ],
        ]));

        $tester = new CommandTester(new CheckDatabaseConnectivityCommand($manager));
        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Database connection: OK', $tester->getDisplay());
        self::assertStringContainsString('Driver:', $tester->getDisplay());
        self::assertStringContainsString('Result: 1', $tester->getDisplay());

        @unlink($databasePath);
    }

    public function testItPrintsFailureWhenTheDatabaseCannotBeOpened(): void
    {
        $manager = new ConnectionManager(new DbConfig([
            'default' => 'sqlite',
            'connections' => [
                'sqlite' => [
                    'driver' => 'sqlite',
                    'database' => sys_get_temp_dir() . '/missing-directory-' . bin2hex(random_bytes(4)) . '/database.sqlite',
                ],
            ],
        ]));

        $tester = new CommandTester(new CheckDatabaseConnectivityCommand($manager));
        $exitCode = $tester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('Database connection: FAILED', $tester->getDisplay());
    }
}
