<?php

declare(strict_types=1);

namespace App\Commands;

use Marwa\DB\Connection\ConnectionManager;
use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'db:check', description: 'Check the configured database connectivity.')]
final class CheckDatabaseConnectivityCommand extends AbstractCommand
{
    public function __construct(
        private ConnectionManager $connectionManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $pdo = $this->connectionManager->getPdo();
            $statement = $pdo->query('SELECT 1');
            $result = $statement !== false ? $statement->fetchColumn() : null;

            $output->writeln('<info>Database connection: OK</info>');
            $output->writeln(sprintf('<info>Driver:</info> %s', (string) $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME)));
            $output->writeln(sprintf('<info>Result:</info> %s', (string) $result));

            return Command::SUCCESS;
        } catch (\Throwable $exception) {
            $output->writeln('<error>Database connection: FAILED</error>');
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

            return Command::FAILURE;
        }
    }
}
