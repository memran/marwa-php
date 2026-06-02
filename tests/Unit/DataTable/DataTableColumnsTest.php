<?php

declare(strict_types=1);

namespace Tests\Unit\DataTable;

use App\Support\DataTable\DataTableColumns;
use PHPUnit\Framework\TestCase;

final class DataTableColumnsTest extends TestCase
{
    public function testBuildReturnsColumnsInProvidedOrderFromColumnOptions(): void
    {
        $columns = new DataTableColumns();
        $state = [
            'query' => '',
            'filter' => 'all',
            'sort' => 'name',
            'direction' => 'asc',
            'page' => 1,
        ];
        $buildUrl = static fn (array $s, array $cols = [], ?string $path = null): string => 'URL';

        $built = $columns->build(
            $state,
            ['name', 'email'],
            ['name' => 'Name', 'email' => 'Email', 'role' => 'Role'],
            ['name', 'email'],
            $buildUrl
        );

        self::assertCount(2, $built);
        self::assertSame('name', $built[0]['key']);
        self::assertSame('Name', $built[0]['label']);
        self::assertTrue($built[0]['sortable']);
        self::assertTrue($built[0]['active']);
        self::assertSame('asc', $built[0]['sort_direction']);
        self::assertSame('email', $built[1]['key']);
        self::assertSame('Email', $built[1]['label']);
        self::assertTrue($built[1]['sortable']);
        self::assertFalse($built[1]['active']);
        self::assertSame('desc', $built[1]['sort_direction']);
    }

    public function testNonSortableColumnDisablesSortControls(): void
    {
        $columns = new DataTableColumns();
        $state = [
            'query' => '',
            'filter' => 'all',
            'sort' => 'name',
            'direction' => 'asc',
            'page' => 1,
        ];
        $buildUrl = static fn (array $s, array $cols = [], ?string $path = null): string => 'URL';

        $built = $columns->build(
            $state,
            ['role'],
            ['role' => 'Role'],
            [],
            $buildUrl
        );

        self::assertFalse($built[0]['sortable']);
        self::assertNull($built[0]['href']);
        self::assertFalse($built[0]['active']);
    }

    public function testSortToggleTogglesAscToDescForActiveColumn(): void
    {
        $columns = new DataTableColumns();
        $state = [
            'query' => 'foo',
            'filter' => 'all',
            'sort' => 'name',
            'direction' => 'asc',
            'page' => 1,
        ];

        $toggled = $columns->sortToggleState($state, 'name');

        self::assertSame('desc', $toggled['direction']);
        self::assertSame('name', $toggled['sort']);
        self::assertSame(1, $toggled['page']);
        self::assertSame('foo', $toggled['query']);
    }

    public function testSortToggleSwitchesToAscWhenTogglingDifferentColumn(): void
    {
        $columns = new DataTableColumns();
        $state = [
            'query' => '',
            'filter' => 'all',
            'sort' => 'name',
            'direction' => 'desc',
            'page' => 3,
        ];

        $toggled = $columns->sortToggleState($state, 'email');

        self::assertSame('email', $toggled['sort']);
        self::assertSame('asc', $toggled['direction']);
        self::assertSame(1, $toggled['page']);
    }

    public function testHiddenColumnsAreSkipped(): void
    {
        $columns = new DataTableColumns();
        $state = [
            'query' => '',
            'filter' => 'all',
            'sort' => 'name',
            'direction' => 'asc',
            'page' => 1,
        ];
        $buildUrl = static fn (array $s, array $cols = [], ?string $path = null): string => 'URL';

        $built = $columns->build(
            $state,
            ['name'],
            ['name' => 'Name', 'email' => 'Email'],
            ['name', 'email'],
            $buildUrl
        );

        self::assertCount(1, $built);
        self::assertSame('name', $built[0]['key']);
    }
}
