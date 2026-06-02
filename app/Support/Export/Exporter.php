<?php

declare(strict_types=1);

namespace App\Support\Export;

interface Exporter
{
    /**
     * @param iterable<mixed> $rows
     * @param list<Column> $columns
     */
    public function build(iterable $rows, array $columns, string $title = ''): string;

    public function contentType(): string;

    public function fileExtension(): string;

    public function fileBaseName(): string;
}
