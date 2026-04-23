<?php

declare(strict_types=1);

namespace App\Modules\BackgroundJobs\Support;

use Marwa\DB\Facades\DB;
use Marwa\Framework\Application;
use Marwa\Framework\Contracts\ScheduleStoreResolverInterface;
use Marwa\Framework\Scheduling\Scheduler;
use Marwa\Framework\Scheduling\Task;

final class BackgroundJobRepository
{
    public function __construct(
        private readonly TaskRegistry $taskRegistry,
        private readonly Application $app,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $configuration = $this->schedulerConfiguration();
        $definitions = $this->taskRegistry->all();
        $records = $this->recordsByName($configuration);
        $jobs = [];

        foreach ($definitions as $definition) {
            $state = $records[$definition['name']] ?? [];
            $jobs[] = $this->mergeTaskState($definition, $state);
        }

        usort($jobs, static function (array $left, array $right): int {
            return [$left['module'], $left['name']] <=> [$right['module'], $right['name']];
        });

        return [
            'driver' => $configuration['driver'],
            'backend_label' => $this->backendLabel($configuration['driver']),
            'cron' => 'php marwa schedule:run --for=60 --sleep=1',
            'jobs' => $jobs,
            'stats' => $this->stats($jobs),
            'state_path' => $configuration['driver'] === 'file' ? ($configuration['file']['path'] ?? '') : null,
            'table' => $configuration['driver'] === 'database' ? ($configuration['database']['table'] ?? 'schedule_jobs') : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $registryId): ?array
    {
        $definition = $this->taskRegistry->get($registryId);

        if ($definition === null) {
            return null;
        }

        $configuration = $this->schedulerConfiguration();
        $record = $this->recordsByName($configuration)[$definition['name']] ?? [];

        return $this->mergeTaskState($definition, is_array($record) ? $record : []);
    }

    /**
     * @return array<string, mixed>
     */
    public function runNow(string $registryId): array
    {
        $task = $this->taskRegistry->makeTask($this->app, $registryId);

        if (!$task instanceof Task) {
            return [
                'ok' => false,
                'message' => 'Task not found.',
            ];
        }

        $time = new \DateTimeImmutable();
        $configuration = $this->schedulerConfiguration();
        try {
            return $this->runWithSchedulerStore($task, $configuration, $time);
        } catch (\Throwable $exception) {
            try {
                $task->run($this->app, $time);
            } catch (\Throwable $taskException) {
                return [
                    'ok' => false,
                    'message' => $taskException->getMessage(),
                ];
            }

            return [
                'ok' => true,
                'message' => 'Task executed successfully. Scheduler storage is unavailable, so status was not recorded.',
            ];
        }
    }

    /**
     * @param array<string, mixed> $configuration
     * @return array<string, mixed>
     */
    private function runWithSchedulerStore(Task $task, array $configuration, \DateTimeImmutable $time): array
    {
        try {
            $store = $this->storeResolver()->resolve($configuration);
        } catch (\Throwable $exception) {
            return $this->runWithoutSchedulerStore($task, $time, $exception->getMessage());
        }

        $lock = null;

        try {
            if ($task->shouldPreventOverlaps()) {
                try {
                    $lock = $store->acquireLock($task, $time, max(60, $task->intervalSeconds() * 2));
                } catch (\Throwable $exception) {
                    return $this->runWithoutSchedulerStore($task, $time, $exception->getMessage());
                }

                if ($lock === null) {
                    $this->safeRecord($store, $task, $time, 'skipped', 'Skipped because it is already running.');

                    return [
                        'ok' => false,
                        'message' => 'Task is already running.',
                    ];
                }
            }

            $task->run($this->app, $time);
            $this->safeRecord($store, $task, $time, 'success');

            return [
                'ok' => true,
                'message' => 'Task executed successfully.',
            ];
        } catch (\Throwable $exception) {
            $this->safeRecord($store, $task, $time, 'failed', $exception->getMessage());

            return [
                'ok' => false,
                'message' => $exception->getMessage(),
            ];
        } finally {
            try {
                $store->releaseLock($task, $lock);
            } catch (\Throwable) {
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function runWithoutSchedulerStore(Task $task, \DateTimeImmutable $time, ?string $reason): array
    {
        try {
            $task->run($this->app, $time);
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'message' => $exception->getMessage(),
            ];
        }

        return [
            'ok' => true,
            'message' => $reason !== null && $reason !== ''
                ? sprintf('Task executed successfully. Scheduler storage is unavailable (%s), so status was not recorded.', $reason)
                : 'Task executed successfully. Scheduler storage is unavailable, so status was not recorded.',
        ];
    }

    private function safeRecord(object $store, Task $task, \DateTimeImmutable $time, string $status, ?string $message = null): void
    {
        try {
            if (method_exists($store, 'record')) {
                $store->record($task, $time, $status, $message);
            }
        } catch (\Throwable) {
        }
    }

    /**
     * @param array<string, mixed> $configuration
     * @return array<string, array<string, mixed>>
     */
    private function recordsByName(array $configuration): array
    {
        return match ($configuration['driver']) {
            'database' => $this->databaseRecords($configuration),
            default => $this->fileRecords($configuration),
        };
    }

    /**
     * @param array<string, mixed> $configuration
     * @return array<string, array<string, mixed>>
     */
    private function databaseRecords(array $configuration): array
    {
        if (!$this->app->has(\Marwa\DB\Connection\ConnectionManager::class)) {
            return [];
        }

        $table = (string) ($configuration['database']['table'] ?? 'schedule_jobs');
        $connection = (string) ($configuration['database']['connection'] ?? 'default');

        try {
            $rows = DB::table($table, $connection)
                ->orderBy('updated_at', 'desc')
                ->get();
        } catch (\Throwable) {
            return [];
        }

        $records = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $name = $this->stringValue($row['name'] ?? null);
            if ($name === null) {
                continue;
            }

            $records[$name] = $this->normalizeRecord($row);
        }

        return $records;
    }

    /**
     * @param array<string, mixed> $configuration
     * @return array<string, array<string, mixed>>
     */
    private function fileRecords(array $configuration): array
    {
        $basePath = $this->stringValue($configuration['file']['path'] ?? null);

        if ($basePath === null || !is_dir($basePath)) {
            return [];
        }

        $stateDir = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'state';
        if (!is_dir($stateDir)) {
            return [];
        }

        $records = [];

        foreach (glob($stateDir . DIRECTORY_SEPARATOR . '*.json') ?: [] as $file) {
            $raw = file_get_contents($file);
            if ($raw === false) {
                continue;
            }

            try {
                /** @var mixed $decoded */
                $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                continue;
            }

            if (!is_array($decoded)) {
                continue;
            }

            $name = $this->stringValue($decoded['name'] ?? null);
            if ($name === null) {
                continue;
            }

            $records[$name] = $this->normalizeRecord($decoded);
        }

        return $records;
    }

    /**
     * @param array<string, mixed> $definition
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function mergeTaskState(array $definition, array $state): array
    {
        $schedule = $definition['schedule'] ?? [];

        return [
            'registry_id' => $definition['registry_id'] ?? $definition['name'],
            'route_id' => $definition['route_id'] ?? $definition['registry_id'] ?? $definition['name'],
            'module' => $definition['module'] ?? 'module',
            'name' => $definition['name'] ?? '',
            'title' => $definition['name'] ?? '',
            'description' => $definition['description'] ?? '',
            'type' => $definition['type'] ?? 'call',
            'schedule' => $this->scheduleLabel(is_array($schedule) ? $schedule : []),
            'status' => $state['status'] ?? 'idle',
            'status_class' => $this->statusClass((string) ($state['status'] ?? 'idle')),
            'result_label' => $this->resultLabel((string) ($state['status'] ?? 'idle')),
            'result_class' => $this->resultClass((string) ($state['status'] ?? 'idle')),
            'last_message' => $state['last_message'] ?? null,
            'last_ran_at' => $state['last_ran_at'] ?? null,
            'last_finished_at' => $state['last_finished_at'] ?? null,
            'last_failed_at' => $state['last_failed_at'] ?? null,
            'last_skipped_at' => $state['last_skipped_at'] ?? null,
            'lock_expires_at' => $state['lock_expires_at'] ?? null,
            'updated_at' => $state['updated_at'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeRecord(array $row): array
    {
        return [
            'status' => $this->stringValue($row['status'] ?? null) ?? 'idle',
            'last_message' => $row['last_message'] ?? null,
            'last_ran_at' => $row['last_ran_at'] ?? null,
            'last_finished_at' => $row['last_finished_at'] ?? null,
            'last_failed_at' => $row['last_failed_at'] ?? null,
            'last_skipped_at' => $row['last_skipped_at'] ?? null,
            'lock_expires_at' => $row['lock_expires_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $jobs
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
            'skipped' => 0,
        ];

        foreach ($jobs as $job) {
            $status = (string) ($job['status'] ?? 'idle');
            $stats[$status] = ($stats[$status] ?? 0) + 1;
        }

        return $stats;
    }

    /**
     * @param array<string, mixed> $schedule
     */
    private function scheduleLabel(array $schedule): string
    {
        if (isset($schedule['everySeconds'])) {
            return sprintf('Every %d seconds', max(1, (int) $schedule['everySeconds']));
        }

        if (isset($schedule['seconds'])) {
            return sprintf('Every %d seconds', max(1, (int) $schedule['seconds']));
        }

        if (($schedule['method'] ?? null) === 'hourly' || !empty($schedule['hourly'])) {
            return 'Hourly';
        }

        if (($schedule['method'] ?? null) === 'daily' || !empty($schedule['daily'])) {
            return 'Daily';
        }

        return 'Every minute';
    }

    private function backendLabel(string $driver): string
    {
        return match ($driver) {
            'database' => 'Database store',
            'cache' => 'Cache store',
            default => 'File store',
        };
    }

    private function statusClass(string $status): string
    {
        return match ($status) {
            'success' => 'text-emerald-700 bg-emerald-50 ring-emerald-200',
            'running' => 'text-sky-700 bg-sky-50 ring-sky-200',
            'failed' => 'text-red-700 bg-red-50 ring-red-200',
            'skipped' => 'text-amber-700 bg-amber-50 ring-amber-200',
            default => 'text-slate-700 bg-slate-50 ring-slate-200',
        };
    }

    private function resultLabel(string $status): string
    {
        return match ($status) {
            'success' => 'Execution recorded',
            'running' => 'Still running',
            'failed' => 'Execution failed',
            'skipped' => 'Execution skipped',
            default => 'Waiting to run',
        };
    }

    private function resultClass(string $status): string
    {
        return match ($status) {
            'success' => 'text-emerald-700 bg-emerald-50 ring-emerald-200',
            'running' => 'text-sky-700 bg-sky-50 ring-sky-200',
            'failed' => 'text-red-700 bg-red-50 ring-red-200',
            'skipped' => 'text-amber-700 bg-amber-50 ring-amber-200',
            default => 'text-slate-700 bg-slate-50 ring-slate-200',
        };
    }

    private function stringValue(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function schedulerConfiguration(): array
    {
        /** @var Scheduler $scheduler */
        $scheduler = $this->app->make(Scheduler::class);

        return $scheduler->configuration();
    }

    private function storeResolver(): ScheduleStoreResolverInterface
    {
        return $this->app->make(ScheduleStoreResolverInterface::class);
    }
}
