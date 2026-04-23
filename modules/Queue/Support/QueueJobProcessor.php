<?php

declare(strict_types=1);

namespace App\Modules\Queue\Support;

use Marwa\Framework\Application;
use Marwa\Framework\Mail\Mailable;
use Marwa\Framework\Queue\MailJob;
use Marwa\Framework\Queue\QueuedJob;

final class QueueJobProcessor
{
    public function __construct(
        private readonly Application $app,
        private readonly QueueCompletionNotifierInterface $notifier
    ) {}

    public function process(QueuedJob $job): int
    {
        try {
            if ($job->name() === MailJob::NAME) {
                $result = $this->processMailJob($job);
                $this->notifyCompletion($job, $result);

                return $result;
            }

            $result = $this->processClassJob($job);
            $this->notifyCompletion($job, $result);

            return $result;
        } catch (\Throwable $exception) {
            $this->notifyFailure($job, $exception->getMessage());

            throw $exception;
        }
    }

    private function processMailJob(QueuedJob $job): int
    {
        return MailJob::fromArray($job->payload())->handle($this->app);
    }

    private function processClassJob(QueuedJob $job): int
    {
        $payload = $job->payload();
        $class = $this->stringValue($payload['class'] ?? null);

        if ($class === null || !class_exists($class)) {
            throw new \RuntimeException(sprintf('No handler registered for queued job [%s].', $job->name()));
        }

        if (is_subclass_of($class, Mailable::class)) {
            /** @var class-string<Mailable> $class */
            $mailable = new $class(is_array($payload['data'] ?? null) ? $payload['data'] : []);

            return $mailable->build($this->app->mailer())->send();
        }

        $instance = $this->app->make($class);

        if (is_callable($instance)) {
            $result = $instance($this->app, new \DateTimeImmutable());

            return is_int($result) ? $result : 1;
        }

        if (is_callable([$instance, 'handle'])) {
            $result = $instance->handle($this->app, new \DateTimeImmutable());

            return is_int($result) ? $result : 1;
        }

        throw new \RuntimeException(sprintf('No handler registered for queued job [%s].', $job->name()));
    }

    private function notifyCompletion(QueuedJob $job, int $result): void
    {
        try {
            $this->notifier->notifyCompleted($job, $result);
        } catch (\Throwable) {
        }
    }

    private function notifyFailure(QueuedJob $job, string $message): void
    {
        try {
            $this->notifier->notifyFailed($job, $message);
        } catch (\Throwable) {
        }
    }

    private function stringValue(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
