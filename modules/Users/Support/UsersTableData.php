<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Auth\Support\AuthManager;
use App\Modules\Users\Models\User;
use App\Support\AdminListState;

final class UsersTableData
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AuthManager $auth,
        private readonly AdminListState $listState,
        private readonly UsersTableColumns $columns,
        private readonly UsersTableRowActions $rowActions,
        private readonly UsersTableToolbar $toolbar,
        private readonly UsersTableExport $exporter,
    ) {}

    /**
     * @param array<string, mixed> $requestParams
     * @param array{data:list<User>,total:int,per_page:int,current_page:int,last_page:int} $usersPage
     * @param array{summary:string,links:list<array{page:string,url:string,active:bool}>} $pagination
     * @return array<string, mixed>
     */
    public function build(
        array $requestParams,
        array $usersPage,
        array $pagination,
        string $exportCsvPath = '/admin/users/export',
        string $exportPdfPath = '/admin/users/export.pdf',
    ): array {
        $state = $this->resolveState($requestParams);
        $visibleColumns = $this->normalizeVisibleColumns($requestParams['columns'] ?? null);
        [$buildUrl, $hiddenFields] = $this->urlHelpers($state, $visibleColumns);
        $rows = $this->buildRows($usersPage['data'], $this->users->protectedAdminId());
        $toolbar = $this->buildToolbar(
            $state,
            $visibleColumns,
            $this->columns->columnOptions(),
            $exportCsvPath,
            $exportPdfPath,
            $buildUrl,
            $hiddenFields
        );

        return $this->assembleSections($state, $visibleColumns, $rows, $pagination, $buildUrl, $hiddenFields, $toolbar);
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     * @param list<array<string, mixed>> $rows
     * @param array{summary:string,links:list<array{page:string,url:string,active:bool}>} $pagination
     * @return array<string, mixed>
     */
    private function assembleSections(
        array $state,
        array $visibleColumns,
        array $rows,
        array $pagination,
        callable $buildUrl,
        callable $hiddenFields,
        array $toolbar,
    ): array {
        return [
            'title' => 'Registered users',
            'description' => 'Search, filter, and review access at a glance.',
            'features' => $this->features(),
            'toolbar' => $toolbar,
            'bulk' => $this->toolbar->buildBulk($state, $rows, $visibleColumns, $hiddenFields),
            'columns' => $this->columns->buildTableColumns($state, $visibleColumns, $buildUrl),
            'rows' => $rows,
            'pagination' => $pagination,
            'empty_state' => $this->emptyState(),
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function features(): array
    {
        return [
            'search' => true,
            'filter' => true,
            'columns' => true,
            'export' => true,
            'sort' => true,
            'pagination' => true,
            'actions' => true,
            'bulk' => true,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function emptyState(): array
    {
        return [
            'title' => 'No users yet',
            'message' => 'Create the first account to start managing access.',
        ];
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     * @return array{0:callable,1:callable}
     */
    private function urlHelpers(array $state, array $visibleColumns): array
    {
        $buildUrl = fn (array $newState, ?array $cols = null): string => $this->buildUsersUrl(
            $newState,
            $cols ?? $visibleColumns
        );
        $hiddenFields = fn (array $params, ?array $cols = null): array => $this->toolbar->hiddenFields(
            $params,
            $cols ?? $visibleColumns
        );

        return [$buildUrl, $hiddenFields];
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     * @param array<string, string> $columnOptions
     * @return array<string, mixed>
     */
    private function buildToolbar(
        array $state,
        array $visibleColumns,
        array $columnOptions,
        string $exportCsvPath,
        string $exportPdfPath,
        callable $buildUrl,
        callable $hiddenFields
    ): array {
        return [
            'search' => $this->toolbar->buildSearch($state, $visibleColumns, $buildUrl, $hiddenFields),
            'filter' => $this->toolbar->buildFilter($state, $visibleColumns, $buildUrl),
            'columns' => $this->toolbar->buildColumnsToolbar($state, $visibleColumns, $columnOptions, $buildUrl, $hiddenFields),
            'exports' => $this->exportActions($state, $visibleColumns, $exportCsvPath, $exportPdfPath, $buildUrl),
            'actions' => $this->printAction(),
        ];
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     * @return list<array<string, string>>
     */
    private function exportActions(
        array $state,
        array $visibleColumns,
        string $exportCsvPath,
        string $exportPdfPath,
        callable $buildUrl
    ): array {
        return [
            [
                'label' => 'CSV',
                'url' => $this->buildUsersUrl($state, $visibleColumns, $exportCsvPath),
                'icon' => 'file-text',
                'format' => 'csv',
                'variant' => 'secondary',
            ],
            [
                'label' => 'PDF',
                'url' => $this->buildUsersUrl($state, $visibleColumns, $exportPdfPath),
                'icon' => 'file',
                'format' => 'pdf',
                'variant' => 'secondary',
            ],
        ];
    }

    /**
     * @return list<array<string, string>>
     */
    private function printAction(): array
    {
        return [[
            'type' => 'button',
            'label' => 'Print',
            'icon' => 'printer',
            'onclick' => 'window.print()',
            'title' => 'Print this page',
            'variant' => 'secondary',
        ]];
    }

    /**
     * @param mixed $columns
     * @return list<string>
     */
    public function normalizeVisibleColumns(mixed $columns): array
    {
        $allowed = array_keys($this->columns->columnOptions());

        if (!is_array($columns)) {
            return $allowed;
        }

        $visible = $this->filterAllowedColumns($columns, $allowed);

        return $visible === [] ? $allowed : $visible;
    }

    /**
     * @param array<int, mixed> $columns
     * @param list<string> $allowed
     * @return list<string>
     */
    private function filterAllowedColumns(array $columns, array $allowed): array
    {
        $visible = [];

        foreach ($columns as $column) {
            if (is_string($column)
                && in_array($column, $allowed, true)
                && !in_array($column, $visible, true)
            ) {
                $visible[] = $column;
            }
        }

        return $visible;
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     */
    public function buildUsersUrl(array $state, array $visibleColumns, string $path = '/admin/users'): string
    {
        return $path . '?' . http_build_query([
            'q' => $state['query'],
            'status' => $state['filter'],
            'sort' => $state['sort'],
            'direction' => $state['direction'],
            'columns' => $visibleColumns,
        ]);
    }

    /**
     * @param list<User> $users
     * @return list<array<string, mixed>>
     */
    public function buildCsv(array $users, array $columns): string
    {
        $resolved = $this->exporter->resolveColumns($columns);

        return $this->exporter->csv()->build($users, $resolved);
    }

    /**
     * @param list<User> $users
     */
    public function writeCsvToFile(string $filePath, array $users, array $columns): void
    {
        $resolved = $this->exporter->resolveColumns($columns);
        $csv = $this->exporter->csv()->build($users, $resolved);
        file_put_contents($filePath, $csv);
    }

    /**
     * @param list<User> $users
     */
    public function writePdfToFile(string $filePath, array $users, array $columns, string $title): void
    {
        $resolved = $this->exporter->resolveColumns($columns);
        $pdf = $this->exporter->pdf()->build($users, $resolved, $title);
        file_put_contents($filePath, $pdf);
    }

    /**
     * @param array<string, mixed> $requestParams
     * @return array{query:string,filter:string,sort:string,direction:string,page:int}
     */
    private function resolveState(array $requestParams): array
    {
        $state = $this->listState->stateFrom($requestParams, 'q', 'status', 'sort', 'direction', 'page');

        return [
            'query' => $state['query'],
            'filter' => $state['filter'],
            'sort' => $state['sort'],
            'direction' => $state['direction'],
            'page' => $state['page'],
        ];
    }

    /**
     * @param list<User> $users
     * @return list<array<string, mixed>>
     */
    private function buildRows(array $users, int|string|null $protectedAdminId): array
    {
        $rows = [];

        foreach ($users as $user) {
            $isProtectedAdmin = $protectedAdminId !== null && (int) $user->getKey() === (int) $protectedAdminId;
            $isTrashed = !empty($user->getAttribute('deleted_at'));
            $isActiveSessionUser = $this->users->isActiveSessionUser($user, $this->auth);

            $rows[] = [
                'bulk' => $this->rowBulkMeta($user, $isProtectedAdmin, $isTrashed, $isActiveSessionUser),
                'cells' => $this->columns->buildCells($user, $isProtectedAdmin),
                'actions' => $this->rowActions->build($user, $isTrashed, $isProtectedAdmin),
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function rowBulkMeta(
        User $user,
        bool $isProtectedAdmin,
        bool $isTrashed,
        bool $isActiveSessionUser
    ): array {
        return [
            'id' => (string) $user->getKey(),
            'disabled' => $isProtectedAdmin || $isTrashed || $isActiveSessionUser,
            'title' => $this->bulkTitleFor($isProtectedAdmin, $isTrashed, $isActiveSessionUser),
            'label' => 'Select ' . (string) $user->getAttribute('name'),
        ];
    }

    private function bulkTitleFor(
        bool $isProtectedAdmin,
        bool $isTrashed,
        bool $isActiveSessionUser
    ): string {
        if ($isProtectedAdmin) {
            return 'The last admin user cannot be selected for bulk actions.';
        }

        if ($isTrashed) {
            return 'Trashed users cannot be selected for bulk actions.';
        }

        if ($isActiveSessionUser) {
            return 'The active session user cannot be selected for bulk actions.';
        }

        return 'Select user for bulk actions.';
    }
}
