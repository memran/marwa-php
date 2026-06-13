<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Theme\ThemeValidator;
use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'theme:validate', description: 'Validate a theme package structure.')]
final class ThemeValidateCommand extends AbstractCommand
{
    public function __construct(
        private readonly ThemeValidator $validator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('theme', InputArgument::REQUIRED, 'Theme folder name.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $theme = trim((string) $input->getArgument('theme'));

        if ($theme === '') {
            $output->writeln('<error>A theme name is required.</error>');

            return Command::INVALID;
        }

        $result = $this->validator->validate($theme);

        $output->writeln(sprintf('Validating theme: %s', $result->displayName()));

        if ($result->hasManifest()) {
            $output->writeln('<info>[OK] Manifest exists</info>');
        } else {
            $output->writeln('<error>[ERROR] Missing manifest.php</error>');
        }

        if ($result->hasLayouts()) {
            $output->writeln('<info>[OK] Required layouts exist</info>');
        }

        if ($result->hasPartials()) {
            $output->writeln('<info>[OK] Required partials exist</info>');
        }

        if ($result->hasComponents()) {
            $output->writeln('<info>[OK] Required components exist</info>');
        }

        if ($result->hasAssets()) {
            $output->writeln('<info>[OK] Declared assets exist</info>');
        }

        foreach ($result->errors() as $error) {
            $output->writeln(sprintf('<error>[ERROR] %s</error>', $error));
        }

        if ($result->isValid()) {
            $output->writeln(sprintf('Theme "%s" is valid.', $result->themeName()));

            return Command::SUCCESS;
        }

        $output->writeln(sprintf('Theme "%s" is invalid.', $result->themeName()));

        return Command::FAILURE;
    }
}
