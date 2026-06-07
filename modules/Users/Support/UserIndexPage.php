<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Users\Models\User;
use App\Support\AdminListState;
use App\Support\DataTable\DataTableView;

final class UserIndexPage
{
    public function __construct(
        private readonly AdminListState $listState,
        private readonly UserDataTable $userTable,
        private readonly DataTableView $dataTable,
    ) {}

    /**
     * @return array{stats:array{total:int,active:int,disabled:int,trashed:int},table:array<string, mixed>}
     */
    public function viewData(): array
    {
        $state = $this->listState->state();
        $columns = request('columns', null);
        $tableParams = $this->listState->tableParams(
            $state,
            $columns,
            $this->dataTable->normalizeVisibleColumns($this->userTable, $columns)
        );
        $status = UserStatus::tryFromFilter($state['filter']);
        $perPage = $this->perPage();

        $pageData = $this->pageData(User::listQuery(
            $state['query'],
            $state['sort'],
            $state['direction'],
            $status
        )->paginate($perPage, $state['page']));

        return [
            'stats' => $this->stats(),
            'table' => $this->dataTable->build(
                $this->userTable,
                $tableParams['request'],
                $pageData,
                $this->pagination($pageData, $tableParams['pagination'])
            ),
        ];
    }

    private function perPage(): int
    {
        $defaultPerPage = (int) config('pagination.default_per_page', 10);
        $configuredPerPage = config('settings.lifecycle.pagination.default_per_page', null);

        return max(1, (int) ($configuredPerPage ?? $defaultPerPage));
    }

    /**
     * @param array{data:array<int, mixed>,total:int,per_page:int,current_page:int,last_page:int} $pageData
     * @return array{data:list<User>,total:int,per_page:int,current_page:int,last_page:int}
     */
    private function pageData(array $pageData): array
    {
        $users = [];
        foreach ($pageData['data'] as $row) {
            if ($row instanceof User) {
                $users[] = $row;
            }
        }

        return [
            'data' => $users,
            'total' => $pageData['total'],
            'per_page' => $pageData['per_page'],
            'current_page' => $pageData['current_page'],
            'last_page' => $pageData['last_page'],
        ];
    }

    /**
     * @return array{total:int,active:int,disabled:int,trashed:int}
     */
    private function stats(): array
    {
        $users = User::collect();
        $activeUsers = $users->filter(static fn (User $user): bool =>
            trim((string) $user->getAttribute('deleted_at')) === '' && (int) $user->getAttribute('is_active') === 1
        );
        $disabledUsers = $users->filter(static fn (User $user): bool =>
            trim((string) $user->getAttribute('deleted_at')) === '' && (int) $user->getAttribute('is_active') === 0
        );
        $trashedUsers = $users->filter(static fn (User $user): bool =>
            trim((string) $user->getAttribute('deleted_at')) !== ''
        );

        return [
            'total' => $activeUsers->count() + $disabledUsers->count(),
            'active' => $activeUsers->count(),
            'disabled' => $disabledUsers->count(),
            'trashed' => $trashedUsers->count(),
        ];
    }

    /**
     * @param array{data:list<User>,total:int,per_page:int,current_page:int,last_page:int} $pageData
     * @param array<string, scalar|list<string>|null> $params
     * @return array{summary:string,links:list<array{page:string,url:string,active:bool}>}
     */
    private function pagination(array $pageData, array $params): array
    {
        $pagination = pagination_view_data($pageData, '/admin/users', $params);

        return [
            'summary' => $pagination['summary'],
            'links' => array_map(
                static fn (array $link): array => [
                    'page' => (string) $link['page'],
                    'url' => $link['url'],
                    'active' => $link['active'],
                ],
                $pagination['links']
            ),
        ];
    }
}
