<?php

declare(strict_types=1);

namespace Tests\Unit\Commands;

use App\Commands\ClearCacheCommand;
use Marwa\Framework\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ClearCacheCommandTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'marwa-cache-command-' . bin2hex(random_bytes(4));
        mkdir($this->basePath . '/storage/cache/views', 0777, true);
        mkdir($this->basePath . '/storage/cache/nested', 0777, true);
        file_put_contents($this->basePath . '/storage/cache/config.php', '<?php return [];');
        file_put_contents($this->basePath . '/storage/cache/routes.php', '<?php return [];');
        file_put_contents($this->basePath . '/storage/cache/modules.php', '<?php return [];');
        file_put_contents($this->basePath . '/storage/cache/views/index.php', '<?php');

        $GLOBALS['marwa_app'] = new Application($this->basePath);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['marwa_app']);
        $this->removeDirectory($this->basePath);

        parent::tearDown();
    }

    public function testCommandClearsTheCacheDirectoryContents(): void
    {
        $command = new ClearCacheCommand();
        $command->setMarwaApplication($GLOBALS['marwa_app']);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        self::assertSame(0, $exitCode);
        self::assertDirectoryExists($this->basePath . '/storage/cache');
        self::assertSame([], array_values(array_diff(scandir($this->basePath . '/storage/cache') ?: [], ['.', '..'])));
        self::assertStringContainsString('Application cache cleared', $tester->getDisplay());
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $itemPath = $item->getPathname();
            if ($item->isDir()) {
                @rmdir($itemPath);
                continue;
            }

            @unlink($itemPath);
        }

        @rmdir($path);
    }
}
