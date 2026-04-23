<?php

declare(strict_types=1);

namespace App\Modules\Queue\Console\Commands;

use App\Modules\Queue\Support\QueueJobProcessor;
use Marwa\Framework\Console\AbstractCommand;
use Marwa\Framework\Queue\QueuedJob;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'queue:work', description: 'Process queued jobs using the globally configured queue backend.')]
final class WorkCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addOption('queue', null, InputOption::VALUE_REQUIRED, 'Queue name to process.', null)
            ->addOption('for', null, InputOption::VALUE_REQUIRED, 'Seconds to keep the worker loop alive.', null)
            ->addOption('sleep', null, InputOption::VALUE_REQUIRED, 'Seconds to sleep between polling attempts.', null)
            ->addOption('tries', null, InputOption::VALUE_REQUIRED, 'Maximum attempts before the job is marked as failed.', '3');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queueName = $this->stringOption($input->getOption('queue'));
        $loopSeconds = $this->intOption($input->getOption('for'), 60);
        $sleepSeconds = $this->intOption($input->getOption('sleep'), 1);
        $tries = max(1, $this->intOption($input->getOption('tries'), 3));

        if ($loopSeconds <= 0 || $sleepSeconds <= 0) {
            $output->writeln('<error>The --for and --sleep options must be positive integers.</error>');

            return Command::INVALID;
        }

        $queue = $this->app()->queue();
        $processor = $this->app()->make(QueueJobProcessor::class);
        $startedAt = time();
        $processed = 0;

        do {
            $job = $this->nextJob($queue, $queueName);

            if ($job instanceof QueuedJob) {
                $processed++;
                $this->processJob($queue, $processor, $job, $tries, $output);
                continue;
            }

            if ((time() - $startedAt + 1) >= $loopSeconds) {
                break;
            }

            sleep($sleepSeconds);
        } while (true);

        if ($processed === 0) {
            $output->writeln('<comment>No queued jobs were due.</comment>');
        }

        return Command::SUCCESS;
    }

    private function processJob(object $queue, QueueJobProcessor $processor, QueuedJob $job, int $tries, OutputInterface $output): void
    {
        try {
            $processor->process($job);

            if (method_exists($queue, 'complete')) {
                $queue->complete($job);
            } elseif (method_exists($queue, 'delete')) {
                $queue->delete($job);
            }

            $output->writeln(sprintf('<info>Processed [%s]</info>', $job->name()));
        } catch (\Throwable $throwable) {
            $message = $throwable->getMessage();

            if ($job->attempts() < $tries) {
                $delay = max(1, (int) config('queue.retryAfter', 90));

                if (method_exists($queue, 'release')) {
                    $queue->release($job, $delay);
                }
                $output->writeln(sprintf('<comment>Released [%s] for retry.</comment>', $job->name()));

                return;
            }

            if (method_exists($queue, 'fail')) {
                $queue->fail($job, $message);
            }

            $output->writeln(sprintf('<error>Job [%s] failed: %s</error>', $job->name(), $message));
        }
    }

    private function nextJob(object $queue, ?string $queueName): ?QueuedJob
    {
        if (!method_exists($queue, 'pop')) {
            return null;
        }

        return $queue->pop($queueName, new \DateTimeImmutable());
    }

    private function stringOption(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function intOption(mixed $value, int $default): int
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $resolved = filter_var($value, FILTER_VALIDATE_INT);

        return is_int($resolved) ? $resolved : 0;
    }
}
