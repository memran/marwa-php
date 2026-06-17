<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Theme\ThemePublisher;
use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'theme:publish', description: 'Publish an admin theme by setting it as the active database-backed admin theme.')]
final class ThemePublishCommand extends AbstractCommand
{
    public function __construct(
        private readonly ThemePublisher $publisher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('theme', InputArgument::REQUIRED, 'Theme folder name, for example: admin-modern');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $theme = trim((string) $input->getArgument('theme'));

        if ($theme === '') {
            $output->writeln('<error>A theme name is required.</error>');

            return Command::INVALID;
        }

        try {
            $result = $this->publisher->publish($theme);
        } catch (\InvalidArgumentException $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

            return Command::INVALID;
        } catch (\Throwable $exception) {
            $output->writeln(sprintf('<error>Unable to publish theme: %s</error>', $exception->getMessage()));

            return Command::FAILURE;
        }

        $output->writeln(sprintf('Published theme: %s', $result->themeName()));
        $output->writeln(sprintf('Published through: %s', $result->channel()));
        $output->writeln(sprintf('<info>Admin theme is now set to "%s".</info>', $result->themeName()));

        return Command::SUCCESS;
    }
}
