<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\BackgroundJobs\Support\TaskRegistry;
use Marwa\Module\Contracts\ModuleRegistryInterface;
use Marwa\Module\Module;
use PHPUnit\Framework\TestCase;

final class BackgroundJobsTaskRegistryTest extends TestCase
{
    private string $modulePath;

    protected function tearDown(): void
    {
        if (isset($this->modulePath) && is_dir($this->modulePath)) {
            $this->removeDirectory($this->modulePath);
        }
    }

    public function test_it_loads_tasks_from_module_manifests(): void
    {
        $module = $this->createModule([
            'slug' => 'reporting',
            'tasks' => [
                'cleanup' => [
                    'type' => 'command',
                    'command' => 'reports:cleanup',
                    'arguments' => [
                        '--days' => 30,
                    ],
                    'schedule' => [
                        'everySeconds' => 300,
                    ],
                    'withoutOverlapping' => true,
                    'description' => 'Cleanup generated reports',
                ],
                'dispatch_digest' => [
                    'type' => 'queue',
                    'job' => 'App\\Jobs\\DispatchDigest',
                    'payload' => [
                        'channel' => 'email',
                    ],
                    'queue' => 'mail',
                    'schedule' => 'hourly',
                ],
                'warm_cache' => [
                    'type' => 'call',
                    'handler' => 'App\\Jobs\\WarmCache@handle',
                    'schedule' => 'daily',
                ],
            ],
        ]);

        $registry = new TaskRegistry($module);
        $tasks = $registry->all();

        self::assertArrayHasKey('reporting.cleanup', $tasks);
        self::assertArrayHasKey('reporting.dispatch_digest', $tasks);
        self::assertArrayHasKey('reporting.warm_cache', $tasks);
        self::assertSame('reporting.cleanup', $tasks['reporting.cleanup']['name']);
        self::assertSame('reporting', $tasks['reporting.cleanup']['module']);
        self::assertSame('command', $tasks['reporting.cleanup']['type']);
        self::assertSame('reports:cleanup', $tasks['reporting.cleanup']['command']);
        self::assertSame(['everySeconds' => 300], $tasks['reporting.cleanup']['schedule']);
        self::assertTrue($tasks['reporting.cleanup']['without_overlapping']);
        self::assertSame('queue', $tasks['reporting.dispatch_digest']['type']);
        self::assertSame('App\\Jobs\\DispatchDigest', $tasks['reporting.dispatch_digest']['job']);
        self::assertSame('mail', $tasks['reporting.dispatch_digest']['queue']);
        self::assertSame(['method' => 'hourly'], $tasks['reporting.dispatch_digest']['schedule']);
        self::assertSame('call', $tasks['reporting.warm_cache']['type']);
        self::assertSame('App\\Jobs\\WarmCache@handle', $tasks['reporting.warm_cache']['handler']);
        self::assertSame(['method' => 'daily'], $tasks['reporting.warm_cache']['schedule']);
    }

    public function test_it_is_empty_when_no_module_tasks_exist(): void
    {
        $module = $this->createModule([
            'slug' => 'empty-module',
        ]);

        $registry = new TaskRegistry($module);

        self::assertSame([], $registry->all());
        self::assertNull($registry->get('empty-module.cleanup'));
    }

    private function createModule(array $manifest): ModuleRegistryInterface
    {
        $this->modulePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'marwa-background-jobs-' . uniqid('', true);
        mkdir($this->modulePath, 0777, true);

        file_put_contents(
            $this->modulePath . DIRECTORY_SEPARATOR . 'manifest.php',
            '<?php return ' . var_export($manifest, true) . ';'
        );

        return new class($this->modulePath, $manifest) implements ModuleRegistryInterface {
            public function __construct(
                private readonly string $path,
                private readonly array $manifest
            ) {}

            public function all(): array
            {
                $slug = (string) ($this->manifest['slug'] ?? 'module');

                return [
                    $slug => new Module($slug, $this->path, $this->manifest),
                ];
            }

            public function has(string $slug): bool
            {
                return ($this->manifest['slug'] ?? null) === $slug;
            }

            public function get(string $slug): ?Module
            {
                return $this->has($slug) ? $this->all()[$slug] : null;
            }

            public function findByPath(string $path): ?Module
            {
                return null;
            }

            public function reload(): void
            {
            }
        };
    }

    private function removeDirectory(string $directory): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());

                continue;
            }

            unlink($file->getPathname());
        }

        rmdir($directory);
    }
}
