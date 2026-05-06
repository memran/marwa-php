<?php

declare(strict_types=1);

namespace App\Commands;

use Marwa\Framework\Console\AbstractCommand;
use Marwa\Framework\Security\RiskAnalyzer;
use Marwa\Framework\Supports\Config;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'security:risk-prune', description: 'Prune recorded security risk signals using the configured retention window.')]
final class SecurityRiskPruneCommand extends AbstractCommand
{
    public function __construct(
        private readonly RiskAnalyzer $riskAnalyzer,
        private readonly Config $config
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'older-than-days',
            null,
            InputOption::VALUE_REQUIRED,
            'Override the configured security risk retention window.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $olderThanDays = null;
        $override = $input->getOption('older-than-days');

        if ($override !== null && $override !== '') {
            $olderThanDays = $this->resolveDays($override);

            if ($olderThanDays === null) {
                $output->writeln('<error>The --older-than-days option must be a positive integer.</error>');

                return Command::INVALID;
            }
        }

        $removed = $olderThanDays === null
            ? $this->riskAnalyzer->prune()
            : $this->riskAnalyzer->prune($olderThanDays);

        $configuredDays = $this->configuredDays();

        $output->writeln(sprintf(
            '<info>Pruned %d security risk signal(s) older than %d day(s).</info>',
            $removed,
            $olderThanDays ?? $configuredDays
        ));

        return Command::SUCCESS;
    }

    private function configuredDays(): int
    {
        $this->config->loadIfExists('security.php');
        $days = $this->config->getInt('security.risk.pruneAfterDays', 30);

        return max(1, $days);
    }

    private function resolveDays(mixed $value): ?int
    {
        $resolved = filter_var($value, FILTER_VALIDATE_INT);

        if (!is_int($resolved) || $resolved < 1) {
            return null;
        }

        return $resolved;
    }
}
