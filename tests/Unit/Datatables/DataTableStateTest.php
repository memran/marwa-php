<?php

declare(strict_types=1);

namespace Tests\Unit\Datatables;

use App\Support\Datatables\DTO\DataTableState;
use PHPUnit\Framework\TestCase;

final class DataTableStateTest extends TestCase
{
    public function testItNormalizesStateAndBuildsPaginationQuery(): void
    {
        $state = DataTableState::fromArray([
            'search' => 'admin',
            'sort' => 'name',
            'direction' => 'sideways',
            'page' => 0,
            'filters' => [
                'status' => 'active',
            ],
            'columns' => ['name', '', 'email', 123, 'created_at'],
        ], 'created_at', 'desc');

        self::assertSame('admin', $state->search());
        self::assertSame('name', $state->sort());
        self::assertSame('desc', $state->direction());
        self::assertSame(1, $state->page());
        self::assertSame(['status' => 'active'], $state->filters());
        self::assertSame(['name', 'email', 'created_at'], $state->columns());
        self::assertSame([
            'search' => 'admin',
            'sort' => 'name',
            'direction' => 'desc',
            'filters' => ['status' => 'active'],
            'columns' => ['name', 'email', 'created_at'],
        ], $state->paginationQuery('search', 'sort', 'direction', 'filters', 'columns'));
        self::assertSame([
            'search' => 'admin',
            'sort' => 'name',
            'direction' => 'desc',
            'page' => 1,
            'filters' => ['status' => 'active'],
            'columns' => ['name', 'email', 'created_at'],
        ], $state->toArray());
    }

    public function testItFallsBackToDefaultSortWhenMissing(): void
    {
        $state = DataTableState::fromArray([
            'search' => '',
            'sort' => '',
            'direction' => 'desc',
            'page' => 2,
            'filters' => [],
            'columns' => null,
        ], 'created_at', 'asc');

        self::assertSame('created_at', $state->sort());
        self::assertSame('desc', $state->direction());
        self::assertNull($state->columns());
    }
}
