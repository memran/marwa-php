<?php

declare(strict_types=1);

namespace App\Modules\BackgroundJobs\Console\Commands;

use Marwa\Framework\Console\AbstractCommand;
use Marwa\Support\File;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'background-jobs:heartbeat', description: 'Write a heartbeat line for scheduler verification.')]
final class HeartbeatCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $line = sprintf(
            "[%s] background-jobs:heartbeat on %s\n",
            date('Y-m-d H:i:s'),
            php_uname('n')
        );

        File::append(storage_path('logs/background-jobs-heartbeat.log'), $line);

        $output->writeln('<info>Heartbeat recorded.</info>');

        return Command::SUCCESS;
    }
}
