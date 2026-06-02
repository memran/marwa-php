<?php

declare(strict_types=1);

namespace Tests\Unit\DataTable;

use App\Support\DataTable\DataTableToolbar;
use PHPUnit\Framework\TestCase;

final class DataTableToolbarTest extends TestCase
{
    public function testBuildSearchIncludesValueAndClearUrl(): void
    {
        $toolbar = new DataTableToolbar();
        $state = [
            'query' => 'admin',
            'filter' => 'all',
            'sort' => 'name',
            'direction' => 'asc',
            'page' => 1,
        ];
        $buildUrl = static fn (array $s, array $cols = [], ?string $path = null): string => '?q=' . $s['query'];
        $hiddenFields = static fn (array $params, array $cols = []): array => [];

        $search = $toolbar->buildSearch(
            '/admin/users',
            $state,
            ['name', 'email'],
            $buildUrl,
            $hiddenFields,
            'Search anything...',
            'Search users'
        );

        self::assertSame('/admin/users', $search['action']);
        self::assertSame('admin', $search['value']);
        self::assertSame('Search anything...', $search['placeholder']);
        self::assertSame('Search users', $search['aria_label']);
        self::assertSame('?q=', $search['clear_url']);
        self::assertSame('Clear search', $search['clear_label']);
    }

    public function testBuildFilterReturnsItemsAndCurrentLabel(): void
    {
        $toolbar = new DataTableToolbar();
        $state = [
            'query' => '',
            'filter' => 'active',
            'sort' => 'name',
            'direction' => 'asc',
            'page' => 1,
        ];
        $buildUrl = static fn (array $s, array $cols = [], ?string $path = null): string => '/url';
        $items = [
            ['label' => 'All', 'href' => '/url?status=all', 'active' => false],
            ['label' => 'Active', 'href' => '/url?status=active', 'active' => true],
        ];

        $filter = $toolbar->buildFilter($state, ['name'], $buildUrl, $items);

        self::assertSame('Filters', $filter['label']);
        self::assertSame('Active', $filter['current_label']);
        self::assertSame($items, $filter['items']);
    }

    public function testBuildColumnsToolbarMarksCheckedAndIncludesResetUrl(): void
    {
        $toolbar = new DataTableToolbar();
        $state = [
            'query' => '',
            'filter' => 'all',
            'sort' => 'name',
            'direction' => 'asc',
            'page' => 1,
        ];
        $buildUrl = static fn (array $s, array $cols = [], ?string $path = null): string => '/admin/users';
        $hiddenFields = static fn (array $params, array $cols = []): array => [];

        $columns = $toolbar->buildColumnsToolbar(
            '/admin/users',
            $state,
            ['name'],
            ['name' => 'Name', 'email' => 'Email', 'role' => 'Role'],
            $buildUrl,
            $hiddenFields
        );

        self::assertSame('Columns', $columns['label']);
        self::assertSame(1, $columns['visible_count']);
        self::assertCount(3, $columns['items']);
        self::assertTrue($columns['items'][0]['checked']);
        self::assertFalse($columns['items'][1]['checked']);
        self::assertFalse($columns['items'][2]['checked']);
    }

    public function testBuildBulkCountsSelectableRows(): void
    {
        $toolbar = new DataTableToolbar();
        $state = [
            'query' => '',
            'filter' => 'all',
            'sort' => 'name',
            'direction' => 'asc',
            'page' => 1,
        ];
        $buildUrl = static fn (array $s, array $cols = [], ?string $path = null): string => '/url';
        $hiddenFields = static fn (array $params, array $cols = []): array => [];
        $rows = [
            ['bulk' => ['disabled' => false]],
            ['bulk' => ['disabled' => true]],
            ['bulk' => ['disabled' => false]],
        ];

        $bulk = $toolbar->buildBulk(
            'users-bulk-form',
            '/admin/users/bulk-delete',
            '/admin/users/bulk-status',
            $state,
            $rows,
            ['name'],
            [['value' => 'active', 'label' => 'Mark active']],
            $hiddenFields,
        );

        self::assertSame('users-bulk-form', $bulk['form_id']);
        self::assertSame(2, $bulk['selectable_count']);
        self::assertSame('Delete selected', $bulk['delete_label']);
        self::assertSame('Mark active', $bulk['status_options'][0]['label']);
    }

    public function testHiddenFieldsAddsColumnsArrayForEachVisibleColumn(): void
    {
        $toolbar = new DataTableToolbar();

        $fields = $toolbar->hiddenFields(
            ['q' => 'admin', 'status' => 'all', 'page' => null, 'empty' => ''],
            ['name', 'email']
        );

        $names = array_column($fields, 'name');
        self::assertContains('q', $names);
        self::assertContains('status', $names);
        self::assertNotContains('page', $names);
        self::assertNotContains('empty', $names);
        self::assertSame(['columns[]', 'columns[]'], array_values(array_filter($names, static fn (string $n): bool => $n === 'columns[]')));
    }

    public function testClearedQueryStateKeepsFilterButResetsQuery(): void
    {
        $toolbar = new DataTableToolbar();
        $state = [
            'query' => 'admin',
            'filter' => 'active',
            'sort' => 'name',
            'direction' => 'asc',
            'page' => 5,
        ];

        $cleared = $toolbar->clearedQueryState($state);

        self::assertSame('', $cleared['query']);
        self::assertSame('active', $cleared['filter']);
        self::assertSame('name', $cleared['sort']);
        self::assertSame(1, $cleared['page']);
    }

    public function testCountSelectableReturnsZeroForEmptyRows(): void
    {
        $toolbar = new DataTableToolbar();

        self::assertSame(0, $toolbar->countSelectable([]));
        self::assertSame(0, $toolbar->countSelectable([['bulk' => ['disabled' => true]]]));
    }
}
