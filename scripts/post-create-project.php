<?php

declare(strict_types=1);

/**
 * This script is executed by Composer after `create-project`.
 * Responsibilities:
 *  - Copy .env.example → .env (if not exists)
 *  - Generate APP_KEY and write into .env
 *  - Ensure storage/logs is writable
 *  - Give short console instructions for Docker + Swoole
 */

//namespace Marwa\;

use RuntimeException;

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

      echo PHP_EOL . ">>> Running Marwa project bootstrap..." . PHP_EOL;

      ensureEnvFile($projectRoot);
      ensureAppKey($projectRoot);
      prepareStorage($projectRoot);

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
            // Replace empty or placeholder APP_KEY line
            $envContent = preg_replace(
                  '/^APP_KEY=.*$/m',
                  'APP_KEY=' . $key,
                  $envContent
            );
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
      // 32 bytes → 64 hex chars, enough for encryption/signing usages.
      return bin2hex(random_bytes(32));
}

/**
 * Make sure storage dirs exist and are writable.
 */
function prepareStorage(string $projectRoot): void
{
      $dirs = [
            $projectRoot . DIRECTORY_SEPARATOR . 'storage',
            $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs',
            $projectRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache',
      ];

      foreach ($dirs as $dir) {
            if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                  throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
            }
      }

      echo " - Ensured storage directories exist" . PHP_EOL;
}

/**
 * Print final message with Docker + Swoole hints.
 */
function printSuccessMessage(): void
{
      echo PHP_EOL;
      echo "Marwa project is ready!" . PHP_EOL;
      echo "Next steps:" . PHP_EOL;
      echo "  1. Copy or adjust your .env if needed." . PHP_EOL;
      echo "  2. Run: composer install" . PHP_EOL;
      echo "  3. Start Docker stack: docker compose up -d" . PHP_EOL;
      echo "  4. Swoole HTTP will listen on http://localhost:9501 (default)." . PHP_EOL;
      echo PHP_EOL;
}
