<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\BackgroundJobs\Support\BackgroundJobRepository;
use App\Modules\BackgroundJobs\Support\TaskRegistry;
use Marwa\Framework\Application;
use Marwa\Module\Contracts\ModuleRegistryInterface;
use Marwa\Module\Module;
use PHPUnit\Framework\TestCase;

final class BackgroundJobsTaskRepositoryTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'marwa-background-jobs-repo-' . uniqid('', true);
        mkdir($this->basePath, 0777, true);
        mkdir($this->basePath . DIRECTORY_SEPARATOR . 'config', 0777, true);
        file_put_contents($this->basePath . DIRECTORY_SEPARATOR . '.env', "APP_ENV=testing\nTIMEZONE=UTC\n");
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);

        unset($GLOBALS['marwa_app']);
    }

    public function test_it_reports_and_runs_module_tasks(): void
    {
        $app = new Application($this->basePath);
        $registry = new TaskRegistry($this->moduleRegistry());
        $repository = new BackgroundJobRepository($registry, $app);

        $overview = $repository->overview();

        self::assertSame(1, $overview['stats']['total']);
        self::assertSame('idle', $overview['jobs'][0]['status']);

        $result = $repository->runNow('background-jobs.digest');

        self::assertTrue($result['ok']);
        self::assertSame('Task executed successfully.', $result['message']);

        $updated = $repository->find('background-jobs.digest');

        self::assertIsArray($updated);
        self::assertSame('success', $updated['status']);
        self::assertNotEmpty($updated['last_ran_at']);
        self::assertNotEmpty($updated['last_finished_at']);
    }

    private function moduleRegistry(): ModuleRegistryInterface
    {
        $manifest = [
            'name' => 'Background Jobs',
            'slug' => 'background-jobs',
            'tasks' => [
                'digest' => [
                    'type' => 'queue',
                    'job' => 'digest:send',
                    'payload' => ['batch' => 1],
                    'schedule' => 'everySeconds',
                    'seconds' => 1,
                ],
            ],
        ];

        return new class($manifest) implements ModuleRegistryInterface {
            public function __construct(private readonly array $manifest) {}

            public function all(): array
            {
                return [
                    'background-jobs' => new Module('background-jobs', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'background-jobs-module', $this->manifest),
                ];
            }

            public function has(string $slug): bool
            {
                return $slug === 'background-jobs';
            }

            public function get(string $slug): ?Module
            {
                return $slug === 'background-jobs' ? $this->all()['background-jobs'] : null;
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
