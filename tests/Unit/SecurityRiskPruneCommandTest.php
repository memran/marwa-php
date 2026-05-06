<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Commands\SecurityRiskPruneCommand;
use Marwa\Framework\Application;
use Marwa\Framework\Security\RiskAnalyzer;
use Marwa\Framework\Supports\Config;
use Psr\Log\NullLogger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class SecurityRiskPruneCommandTest extends TestCase
{
    private string $basePath;
    private string $configDir;
    private string $storageDir;
    private string $logPath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-risk-prune-' . bin2hex(random_bytes(4));
        $this->configDir = $this->basePath . '/config';
        $this->storageDir = $this->basePath . '/storage/security';
        $this->logPath = $this->storageDir . '/risk.jsonl';

        mkdir($this->configDir, 0777, true);
        mkdir($this->storageDir, 0777, true);

        file_put_contents($this->configDir . '/security.php', <<<'PHP'
<?php

return [
    'risk' => [
        'enabled' => true,
        'logPath' => '%LOG_PATH%',
        'pruneAfterDays' => 2,
        'topCount' => 10,
    ],
];
PHP);

        $contents = str_replace('%LOG_PATH%', addslashes($this->logPath), (string) file_get_contents($this->configDir . '/security.php'));
        file_put_contents($this->configDir . '/security.php', $contents);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['marwa_app']);
        $this->removeDirectory($this->basePath);
        parent::tearDown();
    }

    public function test_it_prunes_using_the_configured_retention_window(): void
    {
        file_put_contents(
            $this->logPath,
            implode(PHP_EOL, [
                json_encode([
                    'timestamp' => gmdate(DATE_ATOM, time() - 5 * 86400),
                    'category' => 'csrf',
                    'message' => 'Old signal.',
                    'score' => 90,
                    'context' => [],
                ], JSON_THROW_ON_ERROR),
                json_encode([
                    'timestamp' => gmdate(DATE_ATOM, time() - 3600),
                    'category' => 'throttle',
                    'message' => 'Recent signal.',
                    'score' => 80,
                    'context' => [],
                ], JSON_THROW_ON_ERROR),
            ]) . PHP_EOL
        );

        $command = new SecurityRiskPruneCommand(
            new RiskAnalyzer(
                new Application($this->basePath),
                new Config($this->configDir),
                new NullLogger()
            ),
            new Config($this->configDir)
        );

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Pruned 1 security risk signal(s) older than 2 day(s).', $tester->getDisplay());

        $remaining = file($this->logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        self::assertCount(1, $remaining);
        self::assertStringContainsString('Recent signal.', $remaining[0]);
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($directory);
    }
}
