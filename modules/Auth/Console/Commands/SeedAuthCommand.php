<?php

declare(strict_types=1);

namespace App\Modules\Auth\Console\Commands;

use App\Modules\Auth\Database\Seeders\AuthSeeder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Marwa\Framework\Console\AbstractCommand;

#[AsCommand(name: 'auth:seed', description: 'Seed the starter authentication module.')]
final class SeedAuthCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        (new AuthSeeder())->run();

        $output->writeln('<info>Auth starter seeded.</info>');

        return Command::SUCCESS;
    }
}
