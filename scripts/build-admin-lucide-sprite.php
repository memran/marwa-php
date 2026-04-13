<?php

declare(strict_types=1);

$source = __DIR__ . '/../node_modules/lucide-static/sprite.svg';
$targetDir = __DIR__ . '/../public/themes/admin/assets/icons';
$target = $targetDir . '/lucide.svg';

if (!is_file($source)) {
    fwrite(STDERR, "Lucide sprite not found at {$source}\n");
    exit(1);
}

if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
    fwrite(STDERR, "Unable to create target directory {$targetDir}\n");
    exit(1);
}

if (!copy($source, $target)) {
    fwrite(STDERR, "Unable to copy Lucide sprite to {$target}\n");
    exit(1);
}

fwrite(STDOUT, "Lucide sprite copied to {$target}\n");
