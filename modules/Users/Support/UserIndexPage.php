<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Users\Models\User;
use App\Support\AdminListState;
use App\Support\DataTable\DataTableView;

final class UserIndexPage
{
    public function __construct(
        private readonly UserRepository $users,
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

        $pageData = $this->users->paginatedUsers(
            $state['query'],
            $state['page'],
            null,
            $state['sort'],
            $state['direction'],
            $status
        );

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
