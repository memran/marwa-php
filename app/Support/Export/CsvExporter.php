<?php

declare(strict_types=1);

namespace App\Support\Export;

use RuntimeException;

final class CsvExporter implements Exporter
{
    public function __construct(
        private readonly string $delimiter = ',',
        private readonly string $enclosure = '"',
        private readonly string $escape = '\\',
    ) {}

    public function build(iterable $rows, array $columns, string $title = ''): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new RuntimeException('Unable to open in-memory stream for CSV export.');
        }

        try {
            $this->writeHeader($handle, $columns);

            foreach ($rows as $row) {
                $this->writeRow($handle, $row, $columns);
            }

            rewind($handle);
            $csv = (string) stream_get_contents($handle);
        } finally {
            fclose($handle);
        }

        return $csv;
    }

    public function contentType(): string
    {
        return 'text/csv; charset=UTF-8';
    }

    public function fileExtension(): string
    {
        return 'csv';
    }

    public function fileBaseName(): string
    {
        return 'export';
    }

    /**
     * @param resource $handle
     * @param list<Column> $columns
     */
    private function writeHeader($handle, array $columns): void
    {
        $labels = array_map(static fn (Column $c): string => $c->label, $columns);
        $this->fput($handle, $labels);
    }

    /**
     * @param resource $handle
     * @param list<Column> $columns
     */
    private function writeRow($handle, mixed $row, array $columns): void
    {
        $values = [];

        foreach ($columns as $column) {
            $values[] = $column->resolve($row);
        }

        $this->fput($handle, $values);
    }

    /**
     * @param resource $handle
     * @param list<string> $fields
     */
    private function fput($handle, array $fields): void
    {
        fputcsv($handle, $fields, $this->delimiter, $this->enclosure, $this->escape);
    }
}
