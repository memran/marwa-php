<?php

declare(strict_types=1);

namespace App\Modules\BackgroundJobs\Support;

use Marwa\Framework\Application;
use Marwa\Framework\Scheduling\Task;
use Marwa\Module\Contracts\ModuleRegistryInterface;
use Marwa\Module\Module;

final class TaskRegistry
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $tasks = [];

    public function __construct(private ?ModuleRegistryInterface $moduleRegistry = null)
    {
        $this->loadModuleTasks();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        $this->loadModuleTasks();

        return $this->tasks;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $registryId): ?array
    {
        $this->loadModuleTasks();

        return $this->tasks[$registryId] ?? null;
    }

    public function registerTo(Application $app): int
    {
        $registered = 0;

        foreach ($this->all() as $task) {
            $frameworkTask = $this->makeFrameworkTask($app, $task);

            if ($frameworkTask === null) {
                continue;
            }

            $app->registerTask($frameworkTask);
            $registered++;
        }

        return $registered;
    }

    public function makeTask(Application $app, string $registryId): ?Task
    {
        $definition = $this->get($registryId);

        if ($definition === null) {
            return null;
        }

        return $this->makeFrameworkTask($app, $definition);
    }

    private function loadModuleTasks(): void
    {
        $this->moduleRegistry ??= $this->resolveModuleRegistry();

        if ($this->moduleRegistry === null) {
            return;
        }

        foreach ($this->moduleRegistry->all() as $module) {
            $manifest = $module->manifest();
            $tasks = $manifest['tasks'] ?? $this->loadTasksFromManifestFile($module);

            if (!is_array($tasks)) {
                continue;
            }

            foreach ($tasks as $key => $task) {
                if (!is_array($task)) {
                    continue;
                }

                $normalized = $this->normalizeTask($key, $task, $module->slug());

                if ($normalized !== null) {
                    $this->tasks[$normalized['registry_id']] = $normalized;
                }
            }
        }
    }

    /**
     * @return array<string|int, array<string, mixed>>
     */
    private function loadTasksFromManifestFile(Module $module): array
    {
        $manifestFile = $module->basePath() . DIRECTORY_SEPARATOR . 'manifest.php';

        if (!is_file($manifestFile)) {
            return [];
        }

        /** @var mixed $manifest */
        $manifest = require $manifestFile;

        if (!is_array($manifest)) {
            return [];
        }

        $tasks = $manifest['tasks'] ?? [];

        return is_array($tasks) ? $tasks : [];
    }

    private function resolveModuleRegistry(): ?ModuleRegistryInterface
    {
        try {
            /** @var ModuleRegistryInterface $moduleRegistry */
            $moduleRegistry = app(ModuleRegistryInterface::class);

            return $moduleRegistry;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $task
     * @return array<string, mixed>|null
     */
    private function normalizeTask(string|int $key, array $task, string $moduleSlug): ?array
    {
        $id = $task['id'] ?? $key;

        if (!is_string($id) || trim($id) === '') {
            return null;
        }

        $id = trim($id);
        $registryId = $moduleSlug . '.' . $id;
        $name = $task['name'] ?? $registryId;
        $type = $this->normalizeType($task);

        if ($type === null) {
            return null;
        }

        return [
            'registry_id' => $registryId,
            'route_id' => $this->encodeRegistryId($registryId),
            'id' => $id,
            'name' => is_string($name) && trim($name) !== '' ? trim($name) : $registryId,
            'description' => is_string($task['description'] ?? null) ? trim((string) $task['description']) : '',
            'module' => $moduleSlug,
            'type' => $type,
            'command' => $this->stringValue($task['command'] ?? null),
            'job' => $this->stringValue($task['job'] ?? null),
            'handler' => $task['handler'] ?? null,
            'arguments' => $this->arrayValue($task['arguments'] ?? []),
            'payload' => $this->arrayValue($task['payload'] ?? []),
            'queue' => $this->stringValue($task['queue'] ?? null),
            'schedule' => $this->normalizeSchedule($task),
            'without_overlapping' => (bool) ($task['withoutOverlapping'] ?? $task['without_overlapping'] ?? false),
        ];
    }

    /**
     * @param array<string, mixed> $task
     */
    private function normalizeType(array $task): ?string
    {
        $type = $this->stringValue($task['type'] ?? null);

        if ($type !== null && in_array($type, ['call', 'command', 'queue'], true)) {
            return $type;
        }

        if ($this->stringValue($task['command'] ?? null) !== null) {
            return 'command';
        }

        if ($this->stringValue($task['job'] ?? null) !== null) {
            return 'queue';
        }

        if ($task['handler'] ?? null) {
            return 'call';
        }

        return null;
    }

    /**
     * @param array<string, mixed> $task
     * @return array<string, mixed>
     */
    private function normalizeSchedule(array $task): array
    {
        $schedule = $task['schedule'] ?? null;

        if (is_string($schedule) && trim($schedule) !== '') {
            return ['method' => trim($schedule)];
        }

        if (is_int($schedule)) {
            return ['everySeconds' => max(1, $schedule)];
        }

        if (is_array($schedule)) {
            return $schedule;
        }

        if (isset($task['everySeconds'])) {
            return ['everySeconds' => max(1, (int) $task['everySeconds'])];
        }

        if (isset($task['interval'])) {
            return ['everySeconds' => max(1, (int) $task['interval'])];
        }

        if (!empty($task['everyMinute'])) {
            return ['everyMinute' => true];
        }

        if (!empty($task['hourly'])) {
            return ['hourly' => true];
        }

        if (!empty($task['daily'])) {
            return ['daily' => true];
        }

        return ['everyMinute' => true];
    }

    private function stringValue(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function encodeRegistryId(string $registryId): string
    {
        return str_replace('.', '__', $registryId);
    }

    /**
     * @return array<string, mixed>
     */
    private function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    private function makeFrameworkTask(Application $app, array $task): ?Task
    {
        $name = (string) ($task['name'] ?? $task['registry_id']);
        $description = (string) ($task['description'] ?? '');
        $schedule = $task['schedule'] ?? ['everyMinute' => true];

        $frameworkTask = match ($task['type']) {
            'command' => $this->makeCommandTask($app, $name, $task),
            'queue' => $this->makeQueueTask($app, $name, $task),
            'call' => $this->makeCallTask($app, $name, $task),
            default => null,
        };

        if ($frameworkTask === null) {
            return null;
        }

        if ($description !== '') {
            $frameworkTask->description($description);
        }

        $this->applySchedule($frameworkTask, $schedule);

        if (!empty($task['without_overlapping'])) {
            $frameworkTask->withoutOverlapping();
        }

        return $frameworkTask;
    }

    /**
     * @param array<string, mixed> $task
     */
    private function makeCommandTask(Application $app, string $name, array $task): ?Task
    {
        $command = $this->stringValue($task['command'] ?? null);

        if ($command === null) {
            return null;
        }

        $arguments = $this->arrayValue($task['arguments'] ?? []);

        return $app->schedule()->command($command, $arguments, $name);
    }

    /**
     * @param array<string, mixed> $task
     */
    private function makeQueueTask(Application $app, string $name, array $task): ?Task
    {
        $job = $this->stringValue($task['job'] ?? null);

        if ($job === null) {
            return null;
        }

        $payload = $this->arrayValue($task['payload'] ?? []);
        $queue = $this->stringValue($task['queue'] ?? null);

        return $app->schedule()->queue($job, $payload, $queue, $name);
    }

    /**
     * @param array<string, mixed> $task
     */
    private function makeCallTask(Application $app, string $name, array $task): ?Task
    {
        $handler = $task['handler'] ?? null;

        if ($handler === null) {
            return null;
        }

        $callback = $this->makeCallableHandler($app, $handler);

        if ($callback === null) {
            return null;
        }

        return $app->schedule()->call($callback, $name);
    }

    /**
     * @param mixed $handler
     */
    private function makeCallableHandler(Application $app, mixed $handler): ?callable
    {
        if (is_string($handler)) {
            $handler = trim($handler);

            if ($handler === '') {
                return null;
            }

            if (str_contains($handler, '@')) {
                [$class, $method] = explode('@', $handler, 2);

                return $this->makeMethodCallable($app, $class, $method);
            }

            if (!class_exists($handler)) {
                return null;
            }

            $instance = $app->make($handler);

            if (is_callable($instance)) {
                return static function (Application $application, \DateTimeImmutable $time) use ($instance): mixed {
                    return $instance($application, $time);
                };
            }

            if (method_exists($instance, 'handle')) {
                return static function (Application $application, \DateTimeImmutable $time) use ($instance): mixed {
                    return $instance->handle($application, $time);
                };
            }

            return null;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = array_values($handler);

            if (!is_string($method) || trim($method) === '') {
                return null;
            }

            if (is_string($class)) {
                return $this->makeMethodCallable($app, $class, $method);
            }

            if (is_object($class) && is_callable([$class, $method])) {
                return static function (Application $application, \DateTimeImmutable $time) use ($class, $method): mixed {
                    return $class->{$method}($application, $time);
                };
            }
        }

        return null;
    }

    private function makeMethodCallable(Application $app, string $class, string $method): ?callable
    {
        $class = trim($class);
        $method = trim($method);

        if ($class === '' || $method === '' || !class_exists($class)) {
            return null;
        }

        $instance = $app->make($class);

        if (!is_object($instance) || !is_callable([$instance, $method])) {
            return null;
        }

        return static function (Application $application, \DateTimeImmutable $time) use ($instance, $method): mixed {
            return $instance->{$method}($application, $time);
        };
    }

    /**
     * @param array<string, mixed> $schedule
     */
    private function applySchedule(Task $task, array $schedule): void
    {
        if ($schedule === []) {
            $task->everyMinute();

            return;
        }

        if (isset($schedule['everySeconds'])) {
            $task->everySeconds(max(1, (int) $schedule['everySeconds']));

            return;
        }

        if (isset($schedule['seconds'])) {
            $task->everySeconds(max(1, (int) $schedule['seconds']));

            return;
        }

        if (isset($schedule['everyMinute']) || ($schedule['method'] ?? null) === 'everyMinute') {
            $task->everyMinute();

            return;
        }

        if (isset($schedule['hourly']) || ($schedule['method'] ?? null) === 'hourly') {
            $task->hourly();

            return;
        }

        if (isset($schedule['daily']) || ($schedule['method'] ?? null) === 'daily') {
            $task->daily();

            return;
        }

        if (($schedule['method'] ?? null) === 'everySeconds') {
            $task->everySeconds(max(1, (int) ($schedule['seconds'] ?? $schedule['value'] ?? 1)));

            return;
        }

        if (isset($schedule['method']) && is_string($schedule['method']) && method_exists($task, $schedule['method'])) {
            $method = $schedule['method'];
            $task->{$method}();
        }
    }
}
