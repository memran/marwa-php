<?php

declare(strict_types=1);

/**
 * Post-create bootstrap for the scaffold.
 */

$cwd = getcwd(); // project root

if ($cwd === false) {
    fwrite(STDERR, "Unable to determine current working directory.\n");
    exit(1);
}

try {
    main($cwd, $argv ?? []);
} catch (\Throwable $e) {
    fwrite(STDERR, "[marwa-setup] Error: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

/**
 * @param string $projectRoot
 * @param array<int, string> $argv
 */
function main(string $projectRoot, array $argv): void
{
    $isRootInstall = in_array('--root-install', $argv, true);

    echo PHP_EOL . ">>> Bootstrapping MarwaPHP..." . PHP_EOL;

    ensureEnvFile($projectRoot);
    ensureAppKey($projectRoot);
    prepareRuntimeDirectories($projectRoot);
    buildFrontendAssets($projectRoot);

    if (!$isRootInstall) {
        printSuccessMessage();
    }
}

/**
 * Ensure .env file exists by copying .env.example.
 */
function ensureEnvFile(string $projectRoot): void
{
    $envPath = $projectRoot . DIRECTORY_SEPARATOR . '.env';
    $examplePath = $projectRoot . DIRECTORY_SEPARATOR . '.env.example';

    if (file_exists($envPath)) {
        echo " - .env already exists, skipping copy." . PHP_EOL;
        return;
    }

    if (!file_exists($examplePath)) {
        echo " - No .env.example found, skipping env generation." . PHP_EOL;
        return;
    }

    if (!copy($examplePath, $envPath)) {
        throw new RuntimeException('Failed to copy .env.example to .env');
    }

    echo " - Created .env from .env.example" . PHP_EOL;
}

/**
 * Generate a random APP_KEY and write it into .env if not present.
 */
function ensureAppKey(string $projectRoot): void
{
    $envPath = $projectRoot . DIRECTORY_SEPARATOR . '.env';
    if (!file_exists($envPath)) {
        echo " - .env not found for APP_KEY generation, skipping." . PHP_EOL;
        return;
    }

    $envContent = file_get_contents($envPath);
    if ($envContent === false) {
        throw new RuntimeException('Unable to read .env file');
    }

    if (
        str_contains($envContent, 'APP_KEY=')
        && preg_match('/^APP_KEY=\S+/m', $envContent)
    ) {
        echo " - APP_KEY already set, skipping." . PHP_EOL;
        return;
    }

    $key = generateAppKey();

    if (str_contains($envContent, 'APP_KEY=')) {
        $envContent = preg_replace(
            '/^APP_KEY=.*$/m',
            'APP_KEY=' . $key,
            $envContent
        ) ?? $envContent;
    } else {
        $envContent .= PHP_EOL . 'APP_KEY=' . $key . PHP_EOL;
    }

    if (file_put_contents($envPath, $envContent) === false) {
        throw new RuntimeException('Unable to write APP_KEY to .env');
    }

    echo " - Generated APP_KEY" . PHP_EOL;
}

/**
 * Generate a cryptographically secure random key.
 */
function generateAppKey(): string
{
    return bin2hex(random_bytes(32));
}

/**
 * Make sure runtime directories exist.
 */
function prepareRuntimeDirectories(string $projectRoot): void
{
    $dirs = [
        $projectRoot . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'cache',
        $projectRoot . DIRECTORY_SEPARATOR . 'storage',
        $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache',
        $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs',
        $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'sessions',
      ];

    foreach ($dirs as $dir) {
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
    }

    echo " - Ensured runtime directories exist" . PHP_EOL;
}

/**
 * Install and build the Tailwind assets when Node.js is available.
 */
function buildFrontendAssets(string $projectRoot): void
{
    $packageJson = $projectRoot . DIRECTORY_SEPARATOR . 'package.json';
    $packageLock = $projectRoot . DIRECTORY_SEPARATOR . 'package-lock.json';

    if (!file_exists($packageJson)) {
        echo " - package.json not found, skipping frontend build." . PHP_EOL;
        return;
    }

    if (!commandExists('npm')) {
        echo " - npm is not available, skipping frontend build." . PHP_EOL;
        return;
    }

    if (file_exists($packageLock)) {
        echo " - Installing frontend dependencies with npm ci..." . PHP_EOL;
        $installResult = runCommand(['npm', 'ci', '--no-audit', '--no-fund'], $projectRoot);

        if ($installResult['exitCode'] !== 0) {
            echo " - npm ci failed, skipping Tailwind build." . PHP_EOL;
            return;
        }
    } else {
        echo " - package-lock.json missing, skipping npm ci." . PHP_EOL;
    }

    echo " - Building Tailwind assets..." . PHP_EOL;
    runCommand(['npm', 'run', 'build'], $projectRoot);
}

/**
 * Determine whether a command is available on the current PATH.
 */
function commandExists(string $command): bool
{
    $workingDirectory = getcwd() ?: '.';
    $escapedCommand = implode(' ', array_map('escapeshellarg', ['command', '-v', $command]));
    $output = [];
    $exitCode = 0;

    exec(
        'cd ' . escapeshellarg($workingDirectory) . ' && ' . $escapedCommand . ' 2>/dev/null',
        $output,
        $exitCode
    );

    return $exitCode === 0 && trim(implode(PHP_EOL, $output)) !== '';
}

/**
 * Execute a command and return the exit code plus combined output.
 *
 * @param array<int, string> $command
 * @return array{exitCode: int, output: string}
 */
function runCommand(array $command, string $workingDirectory, bool $quiet = false): array
{
    $escapedCommand = implode(' ', array_map('escapeshellarg', $command));
    $output = [];
    $exitCode = 0;

    exec(
        'cd ' . escapeshellarg($workingDirectory) . ' && ' . $escapedCommand . ' 2>&1',
        $output,
        $exitCode
    );

    $combinedOutput = implode(PHP_EOL, $output);

    if (!$quiet && $combinedOutput !== '') {
        echo $combinedOutput . PHP_EOL;
    }

    if (!$quiet && $exitCode !== 0) {
        echo sprintf(" - Command failed with exit code %d, continuing bootstrap.\n", $exitCode);
    }

    return [
        'exitCode' => $exitCode,
        'output' => $combinedOutput,
    ];
}

/**
 * Print the final developer handoff message.
 */
function printSuccessMessage(): void
{
    echo PHP_EOL;
    echo "MarwaPHP is ready." . PHP_EOL;
    echo "Next steps:" . PHP_EOL;
    echo "  1. Review .env and set your application values." . PHP_EOL;
    echo "  2. Start the Nginx stack: docker compose -f docker/docker-compose.yml up -d" . PHP_EOL;
    echo "  3. Or start the Caddy stack: docker compose -f docker/docker-compose.fpm.yml up -d" . PHP_EOL;
    echo "  4. If you skipped Node.js during setup, run npm ci && npm run build for Tailwind assets." . PHP_EOL;
    echo PHP_EOL;
}
