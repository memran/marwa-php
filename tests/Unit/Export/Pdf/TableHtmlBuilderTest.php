<?php

declare(strict_types=1);

namespace Tests\Unit\Export\Pdf;

use App\Support\Export\Column;
use App\Support\Export\Pdf\TableHtmlBuilder;
use PHPUnit\Framework\TestCase;

final class TableHtmlBuilderTest extends TestCase
{
    public function testBuildsUtf8Document(): void
    {
        $builder = new TableHtmlBuilder();
        $html = $builder->build([], [Column::make('a', 'A', static fn (): string => 'x')], 'Title');

        self::assertStringContainsString('<meta charset="UTF-8">', $html);
        self::assertStringContainsString('<!DOCTYPE html>', $html);
        self::assertStringContainsString('<h1>Title</h1>', $html);
        self::assertStringContainsString('<th>A</th>', $html);
    }

    public function testEmptyTitleOmitsHeading(): void
    {
        $builder = new TableHtmlBuilder();
        $html = $builder->build([['a' => 'b']], [Column::make('a', 'A', static fn (array $r): string => $r['a'])]);

        self::assertStringNotContainsString('<h1>', $html);
    }

    public function testEscapesHtmlEntitiesInValues(): void
    {
        $builder = new TableHtmlBuilder();
        $html = $builder->build(
            [['a' => '<script>alert(1)</script> & "quote"']],
            [Column::make('a', 'A', static fn (array $r): string => $r['a'])]
        );

        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
        self::assertStringContainsString('&amp;', $html);
        self::assertStringContainsString('&quot;', $html);
    }

    public function testPreservesUnicodeCharactersInOutput(): void
    {
        $builder = new TableHtmlBuilder();
        $rows = [
            ['n' => 'Zoë Müller'],
            ['n' => '日本語'],
            ['n' => 'Привет'],
        ];
        $columns = [Column::make('n', 'Name', static fn (array $r): string => $r['n'])];

        $html = $builder->build($rows, $columns, 'Unicode');

        self::assertStringContainsString('Zoë Müller', $html);
        self::assertStringContainsString('日本語', $html);
        self::assertStringContainsString('Привет', $html);
    }

    public function testRendersEmptyStateWhenNoRows(): void
    {
        $builder = new TableHtmlBuilder();
        $html = $builder->build([], [Column::make('a', 'A', static fn (): string => 'x')]);

        self::assertStringContainsString('No data', $html);
        self::assertStringContainsString('colspan="1"', $html);
    }

    public function testColumnOrderIsPreserved(): void
    {
        $builder = new TableHtmlBuilder();
        $columns = [
            Column::make('z', 'Z', static fn (array $r): string => $r['z']),
            Column::make('a', 'A', static fn (array $r): string => $r['a']),
        ];

        $html = $builder->build([['z' => '1', 'a' => '2']], $columns);

        $posZ = strpos($html, '<th>Z</th>');
        $posA = strpos($html, '<th>A</th>');
        self::assertNotFalse($posZ);
        self::assertNotFalse($posA);
        self::assertLessThan($posA, $posZ);
    }
}
