<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$sourceBase = $projectRoot . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'themes';
$targetBase = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'themes';

if (!is_dir($sourceBase)) {
    fwrite(STDERR, "Theme source directory not found: {$sourceBase}\n");
    exit(1);
}

if (!is_dir($targetBase) && !mkdir($targetBase, 0775, true) && !is_dir($targetBase)) {
    fwrite(STDERR, "Unable to create theme target directory: {$targetBase}\n");
    exit(1);
}

$themes = array_filter(scandir($sourceBase) ?: [], static fn (string $entry): bool => $entry !== '.' && $entry !== '..');
$copied = 0;

foreach ($themes as $theme) {
    $sourceAssets = $sourceBase . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . 'assets';

    if (!is_dir($sourceAssets)) {
        continue;
    }

    $targetAssets = $targetBase . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . 'assets';
    syncDirectory($sourceAssets, $targetAssets, $copied);
}

echo sprintf("Synced %d theme asset file(s).\n", $copied);

function syncDirectory(string $source, string $target, int &$copied): void
{
    if (!is_dir($target) && !mkdir($target, 0775, true) && !is_dir($target)) {
        throw new RuntimeException(sprintf('Unable to create directory [%s].', $target));
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $relativePath = substr($item->getPathname(), strlen($source) + 1);
        $destination = $target . DIRECTORY_SEPARATOR . $relativePath;

        if ($item->isDir()) {
            if (!is_dir($destination) && !mkdir($destination, 0775, true) && !is_dir($destination)) {
                throw new RuntimeException(sprintf('Unable to create directory [%s].', $destination));
            }

            continue;
        }

        if (!is_dir(dirname($destination)) && !mkdir(dirname($destination), 0775, true) && !is_dir(dirname($destination))) {
            throw new RuntimeException(sprintf('Unable to create directory [%s].', dirname($destination)));
        }

        if (!copy($item->getPathname(), $destination)) {
            throw new RuntimeException(sprintf('Unable to copy [%s] to [%s].', $item->getPathname(), $destination));
        }

        $copied++;
    }
}
