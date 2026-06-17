<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Theme\ThemeScaffolder;
use App\Theme\ThemeValidator;
use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'theme:make', description: 'Scaffold a new admin theme package from the standard template.')]
final class ThemeMakeCommand extends AbstractCommand
{
    public function __construct(
        private readonly ThemeScaffolder $scaffolder,
        private readonly ThemeValidator $validator
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
            $result = $this->scaffolder->scaffold($theme);
        } catch (\InvalidArgumentException $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception->getMessage()));

            return Command::INVALID;
        } catch (\Throwable $exception) {
            $output->writeln(sprintf('<error>Unable to scaffold theme: %s</error>', $exception->getMessage()));

            return Command::FAILURE;
        }

        $validation = $this->validator->validate($result->themeName());

        $output->writeln(sprintf('Created theme: %s', $result->themeName()));
        $output->writeln(sprintf('Theme views: %s', $result->themePath()));
        $output->writeln(sprintf('Public assets: %s', $result->publicThemePath()));

        if ($validation->isValid()) {
            $output->writeln(sprintf('<info>Theme "%s" is ready.</info>', $result->themeName()));

            return Command::SUCCESS;
        }

        foreach ($validation->errors() as $error) {
            $output->writeln(sprintf('<error>[ERROR] %s</error>', $error));
        }

        $output->writeln(sprintf('<error>Theme "%s" was created but failed validation.</error>', $result->themeName()));

        return Command::FAILURE;
    }
}
