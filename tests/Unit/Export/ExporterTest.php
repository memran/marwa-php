<?php

declare(strict_types=1);

namespace Tests\Unit\Export;

use App\Support\Export\Column;
use App\Support\Export\CsvExporter;
use App\Support\Export\Exporter;
use App\Support\Export\Pdf\DompdfGenerator;
use App\Support\Export\Pdf\TableHtmlBuilder;
use App\Support\Export\PdfExporter;
use PHPUnit\Framework\TestCase;

final class ExporterTest extends TestCase
{
    public function testColumnResolvesValueViaCallback(): void
    {
        $column = Column::make('name', 'Name', static fn (array $row): string => $row['name']);
        self::assertSame('Alice', $column->resolve(['name' => 'Alice']));
        self::assertSame('Bob', $column->resolve(['name' => 'Bob']));
    }

    public function testCsvExporterBuildsHeaderAndRows(): void
    {
        $exporter = new CsvExporter();
        $columns = [
            Column::make('id', 'ID', static fn (array $row): string => (string) $row['id']),
            Column::make('name', 'Name', static fn (array $row): string => $row['name']),
        ];
        $rows = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];

        $csv = $exporter->build($rows, $columns);
        $lines = preg_split('/\r?\n/', trim($csv)) ?: [];

        self::assertCount(3, $lines);
        self::assertSame('ID,Name', $lines[0]);
        self::assertSame('1,Alice', $lines[1]);
        self::assertSame('2,Bob', $lines[2]);
        self::assertSame('text/csv; charset=UTF-8', $exporter->contentType());
        self::assertSame('csv', $exporter->fileExtension());
    }

    public function testCsvExporterEscapesQuotesAndCommas(): void
    {
        $exporter = new CsvExporter();
        $columns = [
            Column::make('note', 'Note', static fn (): string => 'Hello, "World"'),
        ];

        $csv = $exporter->build([['x' => 1]], $columns);

        self::assertStringContainsString('"Hello, ""World"""', $csv);
    }

    public function testCsvExporterAcceptsIterableRows(): void
    {
        $exporter = new CsvExporter();
        $columns = [Column::make('v', 'V', static fn (array $r): string => $r['v'])];

        $generator = static function () {
            yield ['v' => 'a'];
            yield ['v' => 'b'];
        };

        $csv = $exporter->build($generator(), $columns);
        $lines = preg_split('/\r?\n/', trim($csv)) ?: [];

        self::assertSame(['V', 'a', 'b'], $lines);
    }

    public function testPdfExporterProducesValidPdf(): void
    {
        $exporter = $this->makePdfExporter();
        $columns = [
            Column::make('id', 'ID', static fn (array $r): string => (string) $r['id']),
            Column::make('name', 'Name', static fn (array $r): string => $r['name']),
        ];
        $rows = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];

        $pdf = $exporter->build($rows, $columns, 'Test Report');

        self::assertStringStartsWith('%PDF-1.', $pdf);
        self::assertStringContainsString('%%EOF', $pdf);
        self::assertStringContainsString('/Type /Catalog', $pdf);
        self::assertStringContainsString('/Type /Pages', $pdf);
        self::assertSame('application/pdf', $exporter->contentType());
        self::assertSame('pdf', $exporter->fileExtension());
    }

    public function testPdfExporterProducesMultiPageForLargeData(): void
    {
        $exporter = $this->makePdfExporter();
        $columns = [
            Column::make('id', 'ID', static fn (array $r): string => (string) $r['id']),
            Column::make('name', 'Name', static fn (array $r): string => str_repeat($r['name'] . ' longer text to force a taller row ', 3)),
        ];
        $rows = [];
        for ($i = 1; $i <= 100; $i++) {
            $rows[] = ['id' => $i, 'name' => 'User'];
        }

        $pdf = $exporter->build($rows, $columns, 'Large Report');

        preg_match_all('/\\/Type\s*\/Page[^s]/', $pdf, $matches);
        self::assertGreaterThan(1, count($matches[0]), 'PDF should produce multiple pages for large data');
    }

    public function testPdfExporterWithEmptyRows(): void
    {
        $exporter = $this->makePdfExporter();
        $columns = [Column::make('id', 'ID', static fn (): string => 'x')];

        $pdf = $exporter->build([], $columns, 'Empty');

        self::assertStringStartsWith('%PDF-1.', $pdf);
        self::assertStringContainsString('%%EOF', $pdf);
    }

    public function testPdfExporterPreservesUnicodeCharacters(): void
    {
        $exporter = $this->makePdfExporter();
        $columns = [Column::make('name', 'Name', static fn (array $r): string => $r['name'])];
        $rows = [
            ['name' => 'Zoë Müller'],
            ['name' => '日本語'],
            ['name' => 'Привет'],
        ];

        $pdf = $exporter->build($rows, $columns, 'Unicode Test');

        self::assertStringStartsWith('%PDF-1.', $pdf);
        self::assertStringContainsString('%%EOF', $pdf);
        self::assertGreaterThan(2000, strlen($pdf), 'PDF with Unicode should embed fonts and be larger');
    }

    public function testBothExportersImplementTheContract(): void
    {
        self::assertInstanceOf(Exporter::class, new CsvExporter());
        self::assertInstanceOf(Exporter::class, $this->makePdfExporter());
    }

    private function makePdfExporter(): PdfExporter
    {
        return new PdfExporter(new DompdfGenerator(), new TableHtmlBuilder());
    }
}
