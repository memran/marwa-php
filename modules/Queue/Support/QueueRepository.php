<?php

declare(strict_types=1);

namespace App\Modules\Queue\Support;

use Marwa\DB\Facades\DB;
use Marwa\Framework\Application;
use Marwa\Framework\Config\QueueConfig;
use Marwa\Framework\Queue\QueuedJob;

final class QueueRepository
{
    public function __construct(private readonly Application $app) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $configuration = $this->queueConfiguration();
        $jobs = $configuration['driver'] === 'database' ? $this->jobs() : [];

        return [
            'driver' => $configuration['driver'],
            'backend_label' => $configuration['driver'] === 'database' ? 'Database queue' : 'File queue',
            'queue' => $configuration['default'],
            'table' => $configuration['driver'] === 'database' ? $this->table() : null,
            'jobs' => $jobs,
            'stats' => $this->stats($jobs),
            'cron' => 'php marwa queue:work --for=60 --sleep=1',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $jobId): ?array
    {
        if (($this->queueConfiguration()['driver'] ?? 'database') !== 'database') {
            return null;
        }

        $row = DB::table($this->table(), $this->connection())
            ->where('id', '=', $jobId)
            ->first();

        return $row === null ? null : $this->normalizeRow($this->rowToArray($row));
    }

    public function retry(string $jobId): bool
    {
        if (($this->queueConfiguration()['driver'] ?? 'database') !== 'database') {
            return false;
        }

        $row = DB::table($this->table(), $this->connection())
            ->where('id', '=', $jobId)
            ->first();

        if ($row === null) {
            return false;
        }

        $row = $this->rowToArray($row);
        $now = time();
        $attempts = max(0, (int) ($row['attempts'] ?? 0)) + 1;

        $affected = DB::table($this->table(), $this->connection())
            ->where('id', '=', $jobId)
            ->update([
                'attempts' => $attempts,
                'available_at' => $now,
                'reserved_at' => null,
                'reserved_by' => null,
                'completed_at' => null,
                'failed_at' => null,
                'updated_at' => $now,
            ]);

        return $affected > 0;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function push(string $name, array $payload = [], ?string $queue = null, int $delaySeconds = 0): QueuedJob
    {
        $queueBackend = $this->queueBackend();

        if (method_exists($queueBackend, 'push')) {
            return $queueBackend->push($name, $payload, $queue, $delaySeconds);
        }

        throw new \RuntimeException('Configured queue backend does not support pushing jobs.');
    }

    public function pop(?string $queue = null, ?\DateTimeImmutable $now = null): ?QueuedJob
    {
        $queueBackend = $this->queueBackend();

        if (method_exists($queueBackend, 'pop')) {
            return $queueBackend->pop($queue, $now);
        }

        return null;
    }

    public function delete(QueuedJob $job): bool
    {
        $queueBackend = $this->queueBackend();

        if (method_exists($queueBackend, 'complete')) {
            $queueBackend->complete($job);

            return true;
        }

        if (method_exists($queueBackend, 'delete')) {
            return (bool) $queueBackend->delete($job);
        }

        return false;
    }

    public function release(QueuedJob $job, int $delaySeconds = 0): QueuedJob
    {
        $queueBackend = $this->queueBackend();

        if (method_exists($queueBackend, 'release')) {
            return $queueBackend->release($job, $delaySeconds);
        }

        return $job;
    }

    public function fail(QueuedJob $job, ?string $reason = null): bool
    {
        $queueBackend = $this->queueBackend();

        if (method_exists($queueBackend, 'fail')) {
            $queueBackend->fail($job, $reason);

            return true;
        }

        return false;
    }

    public function size(?string $queue = null): int
    {
        $queueBackend = $this->queueBackend();

        if (method_exists($queueBackend, 'pending')) {
            return count($queueBackend->pending($queue));
        }

        return 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function queueConfiguration(): array
    {
        $config = $this->app->make(\Marwa\Framework\Supports\Config::class);
        $config->loadIfExists(QueueConfig::KEY . '.php');

        return QueueConfig::merge(
            $this->app,
            $config->getArray(QueueConfig::KEY, [])
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function jobs(): array
    {
        try {
            $rows = DB::table($this->table(), $this->connection())
                ->orderBy('updated_at', 'desc')
                ->get();
        } catch (\Throwable) {
            return [];
        }

        $jobs = [];

        foreach ($rows as $row) {
            $jobs[] = $this->normalizeRow($row);
        }

        return $jobs;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        $payload = [];

        if (is_string($row['payload'] ?? null) && $row['payload'] !== '') {
            try {
                /** @var mixed $decoded */
                $decoded = json_decode((string) $row['payload'], true, 512, JSON_THROW_ON_ERROR);
                $payload = is_array($decoded) ? $decoded : [];
            } catch (\Throwable) {
                $payload = [];
            }
        }

        $status = $this->statusFromRow($row);

        return [
            'job_id' => (string) ($row['id'] ?? ''),
            'name' => (string) ($row['name'] ?? ''),
            'queue' => (string) ($row['queue'] ?? 'default'),
            'status' => $status,
            'status_class' => $this->statusClass($status),
            'notification_label' => $this->notificationLabel($status),
            'notification_class' => $this->notificationClass($status),
            'attempts' => max(0, (int) ($row['attempts'] ?? 0)),
            'available_at' => $this->formatTimestamp($row['available_at'] ?? null),
            'reserved_at' => $this->formatTimestamp($row['reserved_at'] ?? null),
            'finished_at' => $this->formatTimestamp($row['completed_at'] ?? null),
            'failed_at' => $this->formatTimestamp($row['failed_at'] ?? null),
            'failure_reason' => null,
            'payload' => $payload,
            'payload_json' => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'created_at' => $this->formatTimestamp($row['created_at'] ?? null),
            'updated_at' => $this->formatTimestamp($row['updated_at'] ?? null),
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function statusFromRow(array $row): string
    {
        if (!empty($row['failed_at'])) {
            return 'failed';
        }

        if (!empty($row['completed_at'])) {
            return 'completed';
        }

        if (!empty($row['reserved_at'])) {
            return 'processing';
        }

        return 'pending';
    }

    /**
     * @param array<int, array<string, mixed>> $jobs
     * @return array<string, int>
     */
    private function stats(array $jobs): array
    {
        $stats = [
            'total' => count($jobs),
            'idle' => 0,
            'running' => 0,
            'success' => 0,
            'failed' => 0,
        ];

        foreach ($jobs as $job) {
            $status = (string) ($job['status'] ?? 'pending');

            $mappedStatus = match ($status) {
                'pending' => 'idle',
                'processing' => 'running',
                'completed' => 'success',
                'failed' => 'failed',
                default => 'idle',
            };

            $stats[$mappedStatus]++;
        }

        return $stats;
    }

    private function table(): string
    {
        return (string) ($this->queueConfiguration()['database']['table'] ?? 'queue_jobs');
    }

    private function connection(): string
    {
        $configuration = $this->queueConfiguration();

        return (string) ($configuration['database']['connection'] ?? config('database.default', 'default'));
    }

    private function queueBackend(): object
    {
        return $this->app->queue();
    }

    private function formatTimestamp(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value) || is_int($value) || is_float($value)) {
            $timestamp = is_numeric($value) ? (int) $value : strtotime((string) $value);

            if ($timestamp !== false) {
                return date('Y-m-d H:i:s', $timestamp);
            }
        }

        return null;
    }

    private function statusClass(string $status): string
    {
        return match ($status) {
            'completed' => 'text-emerald-700 bg-emerald-50 ring-emerald-200',
            'processing' => 'text-sky-700 bg-sky-50 ring-sky-200',
            'failed' => 'text-red-700 bg-red-50 ring-red-200',
            'pending' => 'text-amber-700 bg-amber-50 ring-amber-200',
            default => 'text-slate-700 bg-slate-50 ring-slate-200',
        };
    }

    private function notificationLabel(string $status): string
    {
        return match ($status) {
            'completed' => 'Success notification sent',
            'failed' => 'Failure notification sent',
            'processing' => 'Notification pending',
            default => 'Waiting for notification',
        };
    }

    private function notificationClass(string $status): string
    {
        return match ($status) {
            'completed' => 'text-emerald-700 bg-emerald-50 ring-emerald-200',
            'failed' => 'text-red-700 bg-red-50 ring-red-200',
            'processing' => 'text-sky-700 bg-sky-50 ring-sky-200',
            default => 'text-slate-700 bg-slate-50 ring-slate-200',
        };
    }

    /**
     * @param array<string, mixed>|object $row
     * @return array<string, mixed>
     */
    private function rowToArray(array|object $row): array
    {
        return is_array($row) ? $row : (array) $row;
    }
}
