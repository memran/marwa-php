<?php

declare(strict_types=1);

namespace Tests\Unit\Datatables;

use App\Support\Datatables\Action;
use App\Support\Datatables\BulkAction;
use App\Support\Datatables\Column;
use App\Support\Datatables\DataTable;
use App\Support\Datatables\Filter;
use Marwa\DB\Config\Config;
use Marwa\DB\Connection\ConnectionManager;
use Marwa\DB\ORM\Model;
use Marwa\Router\Http\RequestFactory;
use PHPUnit\Framework\TestCase;

final class DataTableTest extends TestCase
{
    private ConnectionManager $manager;

    protected function setUp(): void
    {
        $this->manager = new ConnectionManager(new Config([
            'default' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ]));

        $pdo = $this->manager->getPdo();
        $pdo->exec(
            'CREATE TABLE datatable_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                status TEXT NOT NULL,
                created_at TEXT NOT NULL
            )'
        );

        $pdo->exec("INSERT INTO datatable_users (name, email, status, created_at) VALUES ('Bob', 'bob@example.test', 'inactive', '2026-01-02 10:00:00')");
        $pdo->exec("INSERT INTO datatable_users (name, email, status, created_at) VALUES ('Alice', 'alice@example.test', 'active', '2026-01-01 10:00:00')");
        $pdo->exec("INSERT INTO datatable_users (name, email, status, created_at) VALUES ('Admin', 'admin@example.test', 'active', '2026-01-03 10:00:00')");

        DatatableUser::setConnectionManager($this->manager);
    }

    public function testResultSupportsSearchSortFilterPaginationAndActionVisibility(): void
    {
        $request = RequestFactory::fromArrays(
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/admin/users',
                'HTTP_HOST' => 'example.test',
            ],
            [
                'search' => 'admin',
                'sort' => 'name',
                'direction' => 'asc',
                'filters' => ['status' => 'active'],
                'columns' => ['name', 'email', 'status', 'created_at'],
                'page' => 1,
            ],
            []
        );

        $table = DataTable::fromRequest($request)
            ->query(DatatableUser::query())
            ->columns([
                Column::make('id')->label('ID')->sortable(),
                Column::make('name')->label('Name')->searchable()->sortable(),
                Column::make('email')->label('Email')->searchable(),
                Column::make('status')->label('Status')->filterable()->badge(
                    static fn (mixed $value): string => [
                        'active' => 'green',
                        'inactive' => 'red',
                    ][(string) $value] ?? 'gray'
                ),
                Column::make('created_at')->label('Created')->sortable(),
            ])
            ->filters([
                Filter::select('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->url(static fn (DatatableUser $row): string => '/admin/users/' . $row->getKey()),
                Action::make('secret')
                    ->label('Secret')
                    ->visible(static fn (DatatableUser $row): bool => (string) $row->getAttribute('name') === 'Admin')
                    ->url(static fn (DatatableUser $row): string => '/admin/users/' . $row->getKey() . '/secret'),
            ])
            ->bulkActions([
                BulkAction::make('delete')->label('Delete selected')->url(static fn (array $ids): string => '/admin/users/bulk-delete?ids=' . implode(',', $ids)),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginate(2)
            ->result();

        self::assertSame('Records', $table->title());
        self::assertSame(1, $table->pagination()->currentPage());
        self::assertSame(1, $table->pagination()->total());
        self::assertCount(4, $table->columns());
        self::assertSame('Name', $table->columns()[0]['label']);
        self::assertCount(1, $table->rows());
        self::assertSame('Admin', $table->rows()[0]['cells']['name']['value']);
        self::assertSame('badge', $table->rows()[0]['cells']['status']['type']);
        self::assertSame('green', $table->rows()[0]['cells']['status']['badge']['tone']);
        self::assertCount(2, $table->rows()[0]['actions']);
        self::assertSame('/admin/users/3', $table->rows()[0]['actions'][0]['href']);
        self::assertSame('Showing 1-1 of 1 results', $table->pagination()->summary());
        self::assertStringContainsString('search=admin', $table->pagination()->pages()[0]->url);
        self::assertStringContainsString('filters%5Bstatus%5D=active', $table->pagination()->pages()[0]->url);
        self::assertSame('Name', $table->columnObjects()[0]->label());
        self::assertSame('Admin', $table->rowObjects()[0]->cells()['name']->value());
        self::assertSame('/admin/users/3', $table->rowObjects()[0]->actions()[0]->href());
        self::assertSame('Name', $table->columnObjects()[0]->label);
        self::assertSame('badge', $table->rowObjects()[0]->cells()['status']->type);
        self::assertSame('/admin/users/3', $table->rowObjects()[0]->actions()[0]->href);
        self::assertSame('Admin', $table->rows()[0]['cells']['name']['value']);
        self::assertSame(0, count($table->actions()));
        self::assertSame(1, count($table->bulkActions()));
    }

    public function testInvalidSortColumnFallsBackToDefaultSort(): void
    {
        $request = RequestFactory::fromArrays(
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/admin/users',
                'HTTP_HOST' => 'example.test',
            ],
            [
                'sort' => 'name;drop table users',
                'direction' => 'desc',
            ],
            []
        );

        $table = DataTable::fromRequest($request)
            ->query(DatatableUser::query())
            ->columns([
                Column::make('id')->label('ID')->sortable(),
                Column::make('name')->label('Name')->searchable()->sortable(),
                Column::make('created_at')->label('Created')->sortable(),
            ])
            ->defaultSort('name', 'asc')
            ->paginate(10)
            ->result();

        self::assertSame('Admin', $table->rows()[0]['cells']['name']['value']);
        self::assertSame('Alice', $table->rows()[1]['cells']['name']['value']);
        self::assertSame('Bob', $table->rows()[2]['cells']['name']['value']);
    }

    public function testSelectedIdsAreNormalizedFromRequestBody(): void
    {
        $request = RequestFactory::fromArrays(
            [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/admin/users/bulk-delete',
                'HTTP_HOST' => 'example.test',
            ],
            [],
            [
                'ids' => ['1', '2', '2', 'foo', '0', '-1', '3'],
            ]
        );

        $table = DataTable::fromRequest($request)->query(DatatableUser::query());

        self::assertSame([1, 2, 3], $table->selectedIds());
    }

    public function testEmptyDatasetsExposeEmptyState(): void
    {
        $pdo = $this->manager->getPdo();
        $pdo->exec('DELETE FROM datatable_users');

        $request = RequestFactory::fromArrays(
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/admin/users',
                'HTTP_HOST' => 'example.test',
            ],
            [],
            []
        );

        $table = DataTable::fromRequest($request)
            ->query(DatatableUser::query())
            ->columns([
                Column::make('name')->label('Name')->searchable()->sortable(),
                Column::make('email')->label('Email')->searchable(),
            ])
            ->emptyState([
                'title' => 'No users found',
                'message' => 'Create a user to get started.',
            ])
            ->result();

        self::assertSame([], $table->rows());
        self::assertSame('No users found', $table->emptyState()['title']);
        self::assertSame('Create a user to get started.', $table->emptyState()['message']);
    }
}

final class DatatableUser extends Model
{
    protected static ?string $table = 'datatable_users';
}
