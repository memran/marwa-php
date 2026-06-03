<?php

declare(strict_types=1);

namespace Tests\Unit\DataTable;

use App\Support\DataTable\DataTableColumns;
use App\Support\DataTable\DataTableConfigInterface;
use App\Support\DataTable\DataTableToolbar;
use App\Support\DataTable\DataTableView;
use App\Support\Export\Column;
use PHPUnit\Framework\TestCase;

final class DataTableViewTest extends TestCase
{
    public function testBuildReturnsSectionsInExpectedShape(): void
    {
        $view = new DataTableView(new DataTableColumns(), new DataTableToolbar());
        $config = $this->fakeConfig();
        $pagination = [
            'summary' => 'Showing 1-2 of 2 results',
            'links' => [],
        ];
        $dataPage = [
            'data' => [
                ['id' => 1, 'name' => 'Alice', 'email' => 'a@example.test'],
                ['id' => 2, 'name' => 'Bob', 'email' => 'b@example.test'],
            ],
            'total' => 2,
            'per_page' => 10,
            'current_page' => 1,
            'last_page' => 1,
        ];

        $built = $view->build($config, [
            'q' => '',
            'status' => 'all',
            'sort' => 'name',
            'direction' => 'asc',
            'page' => 1,
            'columns' => ['name', 'email'],
        ], $dataPage, $pagination);

        self::assertSame('Items', $built['title']);
        self::assertSame('Browse all items.', $built['description']);
        self::assertCount(2, $built['rows']);
        self::assertSame('1', $built['rows'][0]['bulk']['id']);
        self::assertSame('Alice', $built['rows'][0]['cells']['name']['value']);
        self::assertSame('link', $built['rows'][0]['actions'][0]['type']);
        self::assertSame('Profile', $built['rows'][0]['actions'][0]['label']);
        self::assertSame('/admin/items/1', $built['rows'][0]['actions'][0]['href']);
        self::assertSame('Showing 1-2 of 2 results', $built['pagination']['summary']);
        self::assertCount(2, $built['columns']);
        self::assertSame('name', $built['columns'][0]['key']);
        self::assertTrue($built['columns'][0]['active']);
        self::assertSame('email', $built['columns'][1]['key']);
        self::assertFalse($built['columns'][1]['active']);
        self::assertCount(2, $built['toolbar']['exports']);
        self::assertSame('CSV', $built['toolbar']['exports'][0]['label']);
        self::assertStringStartsWith('/admin/items/export/csv?', $built['toolbar']['exports'][0]['url']);
        self::assertSame('PDF', $built['toolbar']['exports'][1]['label']);
        self::assertStringStartsWith('/admin/items/export/pdf?', $built['toolbar']['exports'][1]['url']);
    }

    public function testNormalizeVisibleColumnsFallsBackToAllWhenEmptyOrInvalid(): void
    {
        $view = new DataTableView(new DataTableColumns(), new DataTableToolbar());
        $config = $this->fakeConfig();

        self::assertSame(['name', 'email'], $view->normalizeVisibleColumns($config, null));
        self::assertSame(['name', 'email'], $view->normalizeVisibleColumns($config, []));
        self::assertSame(['name', 'email'], $view->normalizeVisibleColumns($config, ['unknown', 'invalid']));
    }

    public function testNormalizeVisibleColumnsDeduplicatesAndFilters(): void
    {
        $view = new DataTableView(new DataTableColumns(), new DataTableToolbar());
        $config = $this->fakeConfig();

        self::assertSame(['name'], $view->normalizeVisibleColumns($config, ['unknown', 'name', 'name']));
    }

    public function testBuildUsersUrlEncodesStateAndColumns(): void
    {
        $view = new DataTableView(new DataTableColumns(), new DataTableToolbar());
        $config = $this->fakeConfig();
        $state = [
            'query' => 'alice',
            'filter' => 'all',
            'sort' => 'name',
            'direction' => 'asc',
            'page' => 1,
        ];

        $url = $view->buildUsersUrl($config, $state, ['name', 'email']);

        self::assertStringStartsWith('/admin/items?', $url);
        self::assertStringContainsString('q=alice', $url);
        self::assertStringContainsString('status=all', $url);
        self::assertStringContainsString('sort=name', $url);
        self::assertStringContainsString('direction=asc', $url);
        self::assertStringContainsString('columns%5B0%5D=name', $url);
        self::assertStringContainsString('columns%5B1%5D=email', $url);
    }

    public function testWriteCsvToFilePersistsValidCsv(): void
    {
        $view = new DataTableView(new DataTableColumns(), new DataTableToolbar());
        $config = $this->fakeConfig();
        $tempFile = tempnam(sys_get_temp_dir(), 'datatable-csv-');
        $state = [
            'query' => '',
            'filter' => 'all',
            'sort' => 'name',
            'direction' => 'asc',
            'page' => 1,
        ];

        $view->writeCsvToFile(
            $config,
            $tempFile,
            [
                ['id' => 1, 'name' => 'Alice', 'email' => 'a@example.test'],
                ['id' => 2, 'name' => 'Bob', 'email' => 'b@example.test'],
            ],
            ['name', 'email'],
            $state
        );

        $content = file_get_contents($tempFile);
        unlink($tempFile);
        self::assertIsString($content);
        self::assertStringContainsString('Name,Email', $content);
        self::assertStringContainsString('Alice,a@example.test', $content);
        self::assertStringContainsString('Bob,b@example.test', $content);
    }

    public function testWritePdfToFilePersistsPdfBinary(): void
    {
        $view = new DataTableView(new DataTableColumns(), new DataTableToolbar());
        $config = $this->fakeConfig();
        $tempFile = tempnam(sys_get_temp_dir(), 'datatable-pdf-');
        $state = [
            'query' => '',
            'filter' => 'all',
            'sort' => 'name',
            'direction' => 'asc',
            'page' => 1,
        ];

        $view->writePdfToFile(
            $config,
            $tempFile,
            [
                ['id' => 1, 'name' => 'Alice', 'email' => 'a@example.test'],
            ],
            ['name', 'email'],
            $state
        );

        $bytes = file_get_contents($tempFile);
        unlink($tempFile);
        self::assertIsString($bytes);
        self::assertGreaterThan(1000, strlen($bytes));
        self::assertStringStartsWith('%PDF-', $bytes);
    }

    public function testResolveExportColumnsDelegatesToConfig(): void
    {
        $view = new DataTableView(new DataTableColumns(), new DataTableToolbar());
        $config = $this->fakeConfig();

        $resolved = $view->resolveExportColumns($config, ['name']);

        self::assertCount(1, $resolved);
        self::assertSame('name', $resolved[0]->key);
    }

    private function fakeConfig(): DataTableConfigInterface
    {
        return new class implements DataTableConfigInterface {
            public function pageTitle(): string
            {
                return 'Items';
            }
            public function pageDescription(): string
            {
                return 'Browse all items.';
            }
            public function searchPlaceholder(): string
            {
                return 'Find...';
            }
            public function columnOptions(): array
            {
                return ['name' => 'Name', 'email' => 'Email'];
            }
            public function sortableKeys(): array
            {
                return ['name', 'email'];
            }
            public function basePath(): string
            {
                return '/admin/items';
            }
            public function defaultSort(): string
            {
                return 'name';
            }
            public function defaultDirection(): string
            {
                return 'asc';
            }
            public function defaultFilter(): string
            {
                return 'all';
            }
            public function rowKey(mixed $row): string
            {
                return (string) ($row['id'] ?? '');
            }
            public function rowIsTrashed(mixed $row): bool
            {
                return false;
            }
            public function filterItems(array $state, array $visibleColumns, callable $buildUrl): array
            {
                return [['label' => 'All', 'href' => '/admin/items', 'active' => true]];
            }
            public function statusOptions(): array
            {
                return [];
            }
            public function hiddenFields(array $params, array $visibleColumns = []): array
            {
                $fields = [];
                foreach ($params as $name => $value) {
                    if ($value !== null && $value !== '') {
                        $fields[] = ['name' => (string) $name, 'value' => (string) $value];
                    }
                }
                return $fields;
            }
            public function buildRow(mixed $row): array
            {
                return [
                    'bulk' => ['id' => (string) $row['id'], 'disabled' => false, 'title' => 'Select', 'label' => 'Select'],
                    'cells' => [
                        'name' => ['type' => 'text', 'value' => $row['name']],
                        'email' => ['type' => 'text', 'value' => $row['email']],
                    ],
                    'actions' => [
                        $this->link('Profile', '/admin/items/' . $row['id']),
                    ],
                ];
            }
            public function buildRowActions(mixed $row, bool $isTrashed, bool $isProtected): array
            {
                return [];
            }
            public function rowBulkMeta(mixed $row, bool $isProtected, bool $isTrashed, bool $isActiveSession): array
            {
                return [];
            }
            public function buildCells(mixed $row, bool $isProtected): array
            {
                return [];
            }
            public function buildExportColumns(): array
            {
                return [
                    Column::make('name', 'Name', static fn (array $r): string => (string) $r['name']),
                    Column::make('email', 'Email', static fn (array $r): string => (string) $r['email']),
                ];
            }
            public function resolveExportColumns(array $visibleKeys): array
            {
                $available = $this->buildExportColumns();
                if ($visibleKeys === []) {
                    return $available;
                }
                $resolved = [];
                foreach ($available as $column) {
                    if (in_array($column->key, $visibleKeys, true)) {
                        $resolved[] = $column;
                    }
                }
                return $resolved;
            }
            public function exports(): array
            {
                return [
                    ['label' => 'CSV', 'url' => '/admin/items/export/csv', 'icon' => 'file-text', 'format' => 'csv', 'variant' => 'secondary'],
                    ['label' => 'PDF', 'url' => '/admin/items/export/pdf', 'icon' => 'file-down', 'format' => 'pdf', 'variant' => 'secondary'],
                ];
            }
            /**
             * @return array{type:string,label:string,href:string,variant:string}
             */
            private function link(string $label, string $href): array
            {
                return ['type' => 'link', 'label' => $label, 'href' => $href, 'variant' => 'secondary'];
            }
        };
    }
}
