<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Queue\Support\QueueCompletionNotifierInterface;
use App\Modules\Queue\Support\QueueJobProcessor;
use Marwa\Framework\Application;
use Marwa\Framework\Queue\QueuedJob;
use PHPUnit\Framework\TestCase;

final class QueueJobProcessorTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'marwa-queue-processor-' . uniqid('', true);
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'config', 0777, true);
        file_put_contents($this->basePath . DIRECTORY_SEPARATOR . '.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);

        unset($GLOBALS['marwa_app']);
    }

    public function test_it_notifies_when_a_queue_job_completes(): void
    {
        $app = new Application($this->basePath);
        $notifier = new FakeQueueCompletionNotifier();
        $processor = new QueueJobProcessor($app, $notifier);

        $job = QueuedJob::fromArray([
            'id' => 'job-1',
            'name' => 'sample:task',
            'queue' => 'default',
            'payload' => [
                'class' => QueueJobProcessorTestHandler::class,
                'data' => [],
            ],
            'attempts' => 0,
            'availableAt' => time(),
            'createdAt' => time(),
        ]);

        $result = $processor->process($job);

        self::assertSame(1, $result);
        self::assertCount(1, $notifier->completedJobs);
        self::assertSame('sample:task', $notifier->completedJobs[0]->name());
    }

    public function test_it_notifies_when_a_queue_job_fails(): void
    {
        $app = new Application($this->basePath);
        $notifier = new FakeQueueCompletionNotifier();
        $processor = new QueueJobProcessor($app, $notifier);

        $job = QueuedJob::fromArray([
            'id' => 'job-2',
            'name' => 'sample:task',
            'queue' => 'default',
            'payload' => [
                'class' => QueueJobProcessorTestFailingHandler::class,
                'data' => [],
            ],
            'attempts' => 0,
            'availableAt' => time(),
            'createdAt' => time(),
        ]);

        try {
            $processor->process($job);
            self::fail('Expected the queue job to throw.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Boom', $exception->getMessage());
        }

        self::assertCount(1, $notifier->failedJobs);
        self::assertSame('sample:task', $notifier->failedJobs[0]->name());
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $current = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($current)) {
                $this->removeDirectory($current);
                continue;
            }

            @unlink($current);
        }

        @rmdir($path);
    }
}

final class QueueJobProcessorTestHandler
{
    public function handle(Application $app, \DateTimeImmutable $time): int
    {
        return 1;
    }
}

final class QueueJobProcessorTestFailingHandler
{
    public function handle(Application $app, \DateTimeImmutable $time): int
    {
        throw new \RuntimeException('Boom');
    }
}

final class FakeQueueCompletionNotifier implements QueueCompletionNotifierInterface
{
    /**
     * @var list<QueuedJob>
     */
    public array $completedJobs = [];

    /**
     * @var list<QueuedJob>
     */
    public array $failedJobs = [];

    public function notifyCompleted(QueuedJob $job, int $result): void
    {
        $this->completedJobs[] = $job;
    }

    public function notifyFailed(QueuedJob $job, string $message): void
    {
        $this->failedJobs[] = $job;
    }
}
