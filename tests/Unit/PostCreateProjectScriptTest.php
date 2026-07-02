<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class PostCreateProjectScriptTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/marwa-post-create-' . bin2hex(random_bytes(6));

        mkdir($this->basePath, 0777, true);
        file_put_contents(
            $this->basePath . '/.env.example',
            "APP_NAME=MarwaPHP\nAPP_KEY=\nDB_CONNECTION=sqlite\n"
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);
    }

    public function testPostCreateProjectBootstrapsRuntimeFilesAndCurrentDockerCommands(): void
    {
        $result = $this->runScript();

        self::assertSame(0, $result['exitCode'], $result['output']);
        self::assertFileExists($this->basePath . '/.env');
        self::assertFileExists($this->basePath . '/database/database.sqlite');
        self::assertDirectoryExists($this->basePath . '/bootstrap/cache');
        self::assertDirectoryExists($this->basePath . '/storage/cache');
        self::assertDirectoryExists($this->basePath . '/storage/logs');
        self::assertDirectoryExists($this->basePath . '/storage/sessions');

        $env = (string) file_get_contents($this->basePath . '/.env');
        self::assertMatchesRegularExpression('/^APP_KEY=[a-f0-9]{64}$/m', $env);

        self::assertStringContainsString('php marwa migrate && php marwa module:migrate && php marwa module:seed', $result['output']);
        self::assertStringContainsString('docker-compose.nginx.yml', $result['output']);
        self::assertStringContainsString('docker-compose.caddy.yml', $result['output']);
        self::assertStringNotContainsString('docker-compose.yml up -d', $result['output']);
        self::assertStringNotContainsString('docker-compose.fpm.yml', $result['output']);
    }

    public function testRootInstallBootstrapsFilesWithoutPrintingFinalHandoff(): void
    {
        $result = $this->runScript(['--root-install']);

        self::assertSame(0, $result['exitCode'], $result['output']);
        self::assertFileExists($this->basePath . '/.env');
        self::assertFileExists($this->basePath . '/database/database.sqlite');
        self::assertStringNotContainsString('MarwaPHP is ready.', $result['output']);
        self::assertStringNotContainsString('Building Tailwind assets', $result['output']);
    }

    /**
     * @param list<string> $arguments
     * @return array{exitCode: int, output: string}
     */
    private function runScript(array $arguments = []): array
    {
        $script = dirname(__DIR__, 2) . '/scripts/post-create-project.php';
        $command = array_merge([PHP_BINARY, $script], $arguments);

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes, $this->basePath);

        if (!is_resource($process)) {
            self::fail('Unable to start post-create-project script.');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [
            'exitCode' => proc_close($process),
            'output' => (string) $stdout . (string) $stderr,
        ];
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

            $itemPath = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($itemPath)) {
                $this->removeDirectory($itemPath);
                continue;
            }

            @unlink($itemPath);
        }

        @rmdir($path);
    }
}
