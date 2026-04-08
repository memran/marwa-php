<?php

declare(strict_types=1);

namespace App\Commands;

use Marwa\Framework\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

#[AsCommand(name: 'app:cache-clear', description: 'Clear all application cache files and directories.')]
final class ClearCacheCommand extends AbstractCommand
{
    protected static ?string $defaultName = 'app:cache-clear';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cacheRoot = cache_path();
        $removed = $this->clearDirectory($cacheRoot);

        $output->writeln(sprintf(
            '<info>Application cache cleared:</info> %s (%d item%s removed)',
            $cacheRoot,
            $removed,
            $removed === 1 ? '' : 's'
        ));

        return Command::SUCCESS;
    }

    private function clearDirectory(string $path): int
    {
        if (!is_dir($path)) {
            if (!mkdir($path, 0775, true) && !is_dir($path)) {
                throw new \RuntimeException(sprintf('Unable to create cache directory "%s".', $path));
            }

            return 0;
        }

        $removed = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $itemPath = $item->getPathname();

            if ($item->isDir()) {
                if (@rmdir($itemPath)) {
                    $removed++;
                }

                continue;
            }

            if (@unlink($itemPath)) {
                $removed++;
            }
        }

        return $removed;
    }
}
