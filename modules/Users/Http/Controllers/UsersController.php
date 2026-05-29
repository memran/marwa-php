<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Auth\Support\AuthManager;
use App\Modules\Users\Models\User;
use App\Support\AdminPagination;
use App\Support\AdminListState;
use App\Modules\Users\Support\UserFormData;
use App\Modules\Users\Support\UserValidationRules;
use App\Modules\Users\Support\UserRepository;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UsersController extends Controller
{
    public function __construct(
        protected readonly UserRepository $users,
        protected readonly UserFormData $forms,
        protected readonly UserValidationRules $rules,
        protected readonly AdminListState $listState,
        protected readonly AdminPagination $pagination,
        protected readonly AuthManager $auth,
        protected readonly \App\Modules\Users\Support\UserActivityService $activity,
    ) {}

    public function index(): ResponseInterface
    {
        $params = $this->listRequestParams();
        $state = $this->listState->stateFrom($params, 'q', 'status', 'sort', 'direction', 'page');
        $visibleColumns = $this->normalizeVisibleColumns($params['columns'] ?? null);
        $users = $this->users->paginatedUsers(
            $state['query'],
            $state['page'],
            null,
            $state['filter'],
            $state['sort'],
            $state['direction']
        );
        $pagination = $this->pagination->viewData($users, '/admin/users', [
            'q' => $state['query'],
            'status' => $state['filter'],
            'sort' => $state['sort'],
            'direction' => $state['direction'],
            'columns' => $visibleColumns,
        ]);
        $table = $this->buildUsersTableData($state, $visibleColumns, $users, $pagination);

        return $this->view('@users/index', [
            'table' => $table,
        ]);
    }

    public function create(): ResponseInterface
    {
        return $this->view('@users/form', $this->forms->formViewData([
            'mode' => 'create',
            'title' => 'Create user',
            'action' => '/admin/users',
            'submit_label' => 'Create user',
        ]));
    }

    public function store(): ResponseInterface
    {
        $validated = $this->validate($this->rules->store());

        $afterState = [
            'name' => trim((string) $validated['name']),
            'email' => User::normalizeEmail((string) $validated['email']),
            'role_id' => (int) $validated['role_id'],
            'is_active' => array_key_exists('is_active', $validated) ? (int) (bool) $validated['is_active'] : 1,
        ];

        if ($duplicate = $this->users->findDuplicateUserByEmail($afterState['email'])) {
            $this->withErrors([
                'email' => [$this->users->duplicateUserMessage($duplicate)],
            ])->withInput([
                'name' => $afterState['name'],
                'email' => $afterState['email'],
                'role_id' => $afterState['role_id'],
                'is_active' => $afterState['is_active'] === 1,
            ]);

            return $this->redirect('/admin/users/create');
        }

        $this->users->createUser($afterState, (string) $validated['password'], $this->auth->user());
        app(\App\Modules\Activity\Support\ActivityRecorder::class)->recordActorAction(
            'user.created',
            'Created user.',
            $this->auth->user(),
            'user',
            null,
            ['state' => $afterState]
        );
        $this->flash('users.notice', 'User created successfully.');

        return $this->redirect('/admin/users');
    }

    public function show(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->users->findUser($vars, true);

        if ($user === null) {
            return $this->response('User not found.', 404);
        }

        return $this->view('@users/profile', [
            'user' => $user,
            'protected_admin_id' => $this->users->protectedAdminId(),
        ]);
    }

    public function edit(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->users->findUser($vars);

        if ($user === null) {
            return $this->response('User not found.', 404);
        }

        return $this->view('@users/form', $this->forms->formViewData([
            'mode' => 'edit',
            'title' => 'Edit user',
            'action' => '/admin/users/' . $user->getKey(),
            'submit_label' => 'Save changes',
            'user' => $user,
        ]));
    }

    public function update(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->users->findUser($vars);

        if ($user === null) {
            return $this->response('User not found.', 404);
        }

        $validated = $this->validate($this->rules->update(), [], [], $request);
        $beforeState = $this->users->userSnapshot($user);
        $currentActive = (int) (bool) $user->getAttribute('is_active');

        $afterState = [
            'name' => trim((string) $validated['name']),
            'email' => User::normalizeEmail((string) $validated['email']),
            'role_id' => (int) $validated['role_id'],
            'is_active' => array_key_exists('is_active', $validated)
                ? (int) (bool) $validated['is_active']
                : $currentActive,
        ];
        $password = array_key_exists('password', $validated) && $validated['password'] !== null
            ? (string) $validated['password']
            : null;
        $passwordChanged = $password !== null && $password !== '';

        if ($duplicate = $this->users->findDuplicateUserByEmail($afterState['email'], (int) $user->getKey())) {
            $this->withErrors([
                'email' => [$this->users->duplicateUserMessage($duplicate)],
            ])->withInput([
                'name' => $afterState['name'],
                'email' => $afterState['email'],
                'role_id' => $afterState['role_id'],
                'is_active' => $afterState['is_active'] === 1,
            ]);

            return $this->redirect('/admin/users/' . $user->getKey() . '/edit');
        }

        if ($this->users->isSelfProtectedAdmin($user, $afterState, $this->auth)) {
            $this->withErrors([
                'is_active' => ['The last admin user cannot disable themselves.'],
            ])->withInput([
                'name' => $afterState['name'],
                'email' => $afterState['email'],
                'role_id' => $afterState['role_id'],
                'is_active' => false,
            ]);

            return $this->redirect('/admin/users/' . $user->getKey() . '/edit');
        }

        if (!$passwordChanged && !$this->users->userStateHasChanges($beforeState, $afterState)) {
            $this->flash('users.notice', 'No changes detected.');

            return $this->redirect('/admin/users/' . $user->getKey() . '/edit');
        }

        $this->users->updateUser($user, $afterState, $password, $this->auth->user());

        $payload = $afterState['is_active'] !== $beforeState['is_active']
            ? $this->activity->statusChangedPayload($user, $beforeState, $afterState)
            : $this->activity->updatedPayload($user, $beforeState, $afterState, $passwordChanged);

        app(\App\Modules\Activity\Support\ActivityRecorder::class)->recordActorAction(
            $payload['action'],
            $payload['description'],
            $this->auth->user(),
            $payload['subjectType'],
            $payload['subjectId'],
            $payload['details']
        );
        $this->flash('users.notice', 'User updated successfully.');

        return $this->redirect('/admin/users');
    }

    public function restore(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->users->findUser($vars, true);

        if ($user === null) {
            return $this->response('User not found.', 404);
        }

        if (!empty($user->getAttribute('deleted_at'))) {
            $duplicate = $this->users->findDuplicateUserByEmail((string) $user->getAttribute('email'), (int) $user->getKey());

            if ($duplicate !== null) {
                $this->flash('users.notice', $this->users->duplicateUserMessage($duplicate));

                return $this->redirect('/admin/users');
            }
        }

        if ($this->users->restoreUser($user, $this->auth->user())) {
            app(\App\Modules\Activity\Support\ActivityRecorder::class)->recordActorAction(
                'user.restored',
                'Restored user account.',
                $this->auth->user(),
                'user',
                (int) $user->getKey(),
                ['state' => $this->users->userSnapshot($user)]
            );
            $this->flash('users.notice', 'User restored successfully.');
        } else {
            $this->flash('users.notice', 'Unable to restore the selected user.');
        }

        return $this->redirect('/admin/users');
    }

    public function delete(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->users->findUser($vars);

        if ($user === null) {
            return $this->response('User not found.', 404);
        }

        if ($this->users->isLastAdminUser($user)) {
            $this->flash('users.notice', 'You cannot delete the last admin user.');

            return $this->redirect('/admin/users');
        }

        if ($this->users->isActiveSessionUser($user, $this->auth)) {
            $this->flash('users.notice', 'You cannot delete the active session user.');

            return $this->redirect('/admin/users');
        }

        $this->users->deleteUser($user, $this->auth->user());
        app(\App\Modules\Activity\Support\ActivityRecorder::class)->recordActorAction(
            'user.deleted',
            'Soft deleted user account.',
            $this->auth->user(),
            'user',
            (int) $user->getKey(),
            ['state' => $this->users->userSnapshot($user)]
        );
        $this->flash('users.notice', 'User deleted successfully.');

        return $this->redirect('/admin/users');
    }

    public function export(): ResponseInterface
    {
        $params = $this->listRequestParams();
        $state = $this->listState->stateFrom($params, 'q', 'status', 'sort', 'direction', 'page');
        $visibleColumns = $this->normalizeVisibleColumns($params['columns'] ?? null);
        $users = $this->users->listUsers(
            $state['query'],
            $state['filter'],
            $state['sort'],
            $state['direction']
        );
        $csv = $this->buildUsersCsv($users, $visibleColumns);

        return $this->response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="users-export.csv"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function bulkDelete(): ResponseInterface
    {
        $params = $this->listRequestParams();
        $state = $this->listState->stateFrom($params, 'q', 'status', 'sort', 'direction', 'page');
        $visibleColumns = $this->normalizeVisibleColumns($params['columns'] ?? null);
        $selectedIds = $this->bulkSelectedIds();

        if ($selectedIds === []) {
            $this->flash('users.notice', 'Select at least one user before deleting.');

            return $this->redirectToUsersIndex($state, $visibleColumns);
        }

        $users = $this->users->usersByIds($selectedIds);
        $deleted = 0;
        $skipped = 0;

        foreach ($users as $user) {
            if ($this->isBulkProtected($user)) {
                $skipped++;
                continue;
            }

            if (!empty($user->getAttribute('deleted_at'))) {
                $skipped++;
                continue;
            }

            $payload = $this->activity->deletedPayload($user);
            $this->users->deleteUser($user, $this->auth->user());
            app(\App\Modules\Activity\Support\ActivityRecorder::class)->recordActorAction(
                $payload['action'],
                $payload['description'],
                $this->auth->user(),
                $payload['subjectType'],
                $payload['subjectId'],
                $payload['details']
            );
            $deleted++;
        }

        $this->flash('users.notice', $this->bulkNoticeMessage('deleted', $deleted, $skipped));

        return $this->redirectToUsersIndex($state, $visibleColumns);
    }

    public function bulkStatus(): ResponseInterface
    {
        $params = $this->listRequestParams();
        $state = $this->listState->stateFrom($params, 'q', 'status', 'sort', 'direction', 'page');
        $visibleColumns = $this->normalizeVisibleColumns($params['columns'] ?? null);
        $selectedIds = $this->bulkSelectedIds();
        $targetStatus = strtolower(trim((string) request('bulk_status', '')));

        if (!in_array($targetStatus, ['active', 'disabled'], true)) {
            $this->flash('users.notice', 'Choose a valid status before updating selected users.');

            return $this->redirectToUsersIndex($state, $visibleColumns);
        }

        if ($selectedIds === []) {
            $this->flash('users.notice', 'Select at least one user before updating status.');

            return $this->redirectToUsersIndex($state, $visibleColumns);
        }

        $users = $this->users->usersByIds($selectedIds);
        $updated = 0;
        $skipped = 0;
        $afterStatus = $targetStatus === 'active' ? 1 : 0;

        foreach ($users as $user) {
            if ($this->isBulkProtected($user)) {
                $skipped++;
                continue;
            }

            if (!empty($user->getAttribute('deleted_at'))) {
                $skipped++;
                continue;
            }

            if ((int) $user->getAttribute('is_active') === $afterStatus) {
                $skipped++;
                continue;
            }

            $beforeState = $this->users->userSnapshot($user);
            $afterState = [
                'name' => (string) $user->getAttribute('name'),
                'email' => User::normalizeEmail((string) $user->getAttribute('email')),
                'role_id' => (int) $user->getAttribute('role_id'),
                'is_active' => $afterStatus,
            ];
            $payload = $this->activity->statusChangedPayload($user, $beforeState, $afterState);
            $this->users->updateUser($user, $afterState, null, $this->auth->user());
            app(\App\Modules\Activity\Support\ActivityRecorder::class)->recordActorAction(
                $payload['action'],
                $payload['description'],
                $this->auth->user(),
                $payload['subjectType'],
                $payload['subjectId'],
                $payload['details']
            );
            $updated++;
        }

        $this->flash('users.notice', $this->bulkNoticeMessage('updated', $updated, $skipped));

        return $this->redirectToUsersIndex($state, $visibleColumns);
    }

    /**
     * @return array{q:string,status:string,sort:string,direction:string,page:int,columns:mixed}
     */
    private function listRequestParams(): array
    {
        return [
            'q' => request('q', ''),
            'status' => request('status', 'all'),
            'sort' => request('sort', 'created_at'),
            'direction' => request('direction', 'desc'),
            'page' => request('page', 1),
            'columns' => request('columns', []),
        ];
    }

    /**
     * @return list<int>
     */
    private function bulkSelectedIds(): array
    {
        $ids = request('ids', []);

        if (!is_array($ids)) {
            return [];
        }

        $selected = [];

        foreach ($ids as $id) {
            if (!is_numeric($id)) {
                continue;
            }

            $normalized = (int) $id;
            if ($normalized <= 0 || in_array($normalized, $selected, true)) {
                continue;
            }

            $selected[] = $normalized;
        }

        return $selected;
    }

    private function isBulkProtected(User $user): bool
    {
        return $this->users->isActiveSessionUser($user, $this->auth) || $this->users->isLastAdminUser($user);
    }

    private function bulkNoticeMessage(string $verb, int $processed, int $skipped): string
    {
        if ($processed === 0) {
            return $skipped > 0
                ? 'No selected users could be ' . $verb . '.'
                : 'No selected users were changed.';
        }

        $message = ucfirst($verb) . ' ' . $processed . ' user' . ($processed === 1 ? '' : 's') . '.';

        if ($skipped > 0) {
            $message .= ' Skipped ' . $skipped . ' protected user' . ($skipped === 1 ? '' : 's') . '.';
        }

        return $message;
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     */
    private function redirectToUsersIndex(array $state, array $visibleColumns): ResponseInterface
    {
        return $this->redirect($this->buildUsersUrl([
            'q' => $state['query'],
            'status' => $state['filter'],
            'sort' => $state['sort'],
            'direction' => $state['direction'],
            'page' => $state['page'],
            'columns' => $visibleColumns,
        ]));
    }

    /**
     * @return array<string, string>
     */
    private function columnOptions(): array
    {
        return [
            'name' => 'Name',
            'email' => 'Email',
            'role' => 'Role',
            'status' => 'Status',
            'last_login' => 'Last login',
        ];
    }

    /**
     * @param mixed $columns
     * @return list<string>
     */
    private function normalizeVisibleColumns(mixed $columns): array
    {
        $allowed = array_keys($this->columnOptions());

        if (!is_array($columns)) {
            return $allowed;
        }

        $visible = [];
        foreach ($columns as $column) {
            if (!is_string($column)) {
                continue;
            }

            if (!in_array($column, $allowed, true)) {
                continue;
            }

            if (!in_array($column, $visible, true)) {
                $visible[] = $column;
            }
        }

        return $visible === [] ? $allowed : $visible;
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     * @param array{data:list<User>,total:int,per_page:int,current_page:int,last_page:int} $usersPage
     * @param array{summary:string,links:list<array{page:string,url:string,active:bool}>} $pagination
     * @return array<string, mixed>
     */
    private function buildUsersTableData(array $state, array $visibleColumns, array $usersPage, array $pagination): array
    {
        $columnOptions = $this->columnOptions();
        $currentAdminId = $this->users->protectedAdminId();
        $rows = $this->buildTableRows($usersPage['data'], $currentAdminId);

        return [
            'title' => 'Registered users',
            'description' => 'Search, filter, and review access at a glance.',
            'features' => [
                'search' => true,
                'filter' => true,
                'columns' => true,
                'export' => true,
                'sort' => true,
                'pagination' => true,
                'actions' => true,
                'bulk' => true,
            ],
            'toolbar' => [
                'search' => [
                    'action' => '/admin/users',
                    'value' => $state['query'],
                    'placeholder' => 'Search anything...',
                    'aria_label' => 'Search users',
                    'submit_label' => 'Search users',
                    'clear_label' => 'Clear search',
                    'clear_url' => $this->buildUsersUrl([
                        'q' => '',
                        'status' => $state['filter'],
                        'sort' => $state['sort'],
                        'direction' => $state['direction'],
                        'columns' => $visibleColumns,
                    ]),
                    'hidden_fields' => $this->hiddenFields([
                        'status' => $state['filter'],
                        'sort' => $state['sort'],
                        'direction' => $state['direction'],
                    ], $visibleColumns),
                ],
                'filter' => [
                    'label' => 'Filters',
                    'current_label' => ucfirst(str_replace('_', ' ', $state['filter'])),
                    'items' => $this->buildUserFilterItems($state, $visibleColumns),
                ],
                'columns' => [
                    'label' => 'Columns',
                    'legend' => 'Visible columns',
                    'visible_count' => count($visibleColumns),
                    'action' => '/admin/users',
                    'reset_url' => $this->buildUsersUrl([
                        'q' => $state['query'],
                        'status' => $state['filter'],
                        'sort' => $state['sort'],
                        'direction' => $state['direction'],
                        'columns' => array_keys($columnOptions),
                    ]),
                    'hidden_fields' => $this->hiddenFields([
                        'q' => $state['query'],
                        'status' => $state['filter'],
                        'sort' => $state['sort'],
                        'direction' => $state['direction'],
                    ]),
                    'items' => $this->buildColumnItems($visibleColumns),
                    'submit_label' => 'Apply',
                    'reset_label' => 'Reset',
                ],
                'export_url' => $this->buildUsersUrl([
                    'q' => $state['query'],
                    'status' => $state['filter'],
                    'sort' => $state['sort'],
                    'direction' => $state['direction'],
                    'columns' => $visibleColumns,
                ], '/admin/users/export'),
                'export_label' => 'Export',
                'export_icon' => 'download',
                'actions' => [
                    [
                        'type' => 'button',
                        'label' => 'Print',
                        'icon' => 'printer',
                        'onclick' => 'window.print()',
                        'title' => 'Print this page',
                        'variant' => 'secondary',
                    ],
                ],
            ],
            'bulk' => [
                'form_id' => 'users-bulk-form',
                'action_delete_url' => '/admin/users/bulk-delete',
                'action_status_url' => '/admin/users/bulk-status',
                'selectable_count' => count(array_filter($rows, static fn (array $row): bool => !($row['bulk']['disabled'] ?? false))),
                'select_all_label' => 'Select all',
                'selection_header_label' => 'Select rows',
                'status_placeholder' => 'Bulk status',
                'status_label' => 'Update status',
                'status_icon' => 'refresh-cw',
                'status_options' => [
                    ['value' => 'active', 'label' => 'Mark active'],
                    ['value' => 'disabled', 'label' => 'Mark disabled'],
                ],
                'delete_label' => 'Delete selected',
                'delete_icon' => 'trash-2',
                'delete_confirm' => 'Delete the selected users?',
                'hidden_fields' => $this->hiddenFields([
                    'q' => $state['query'],
                    'status' => $state['filter'],
                    'sort' => $state['sort'],
                    'direction' => $state['direction'],
                    'page' => $state['page'],
                ], $visibleColumns),
            ],
            'columns' => $this->buildTableColumns($state, $visibleColumns),
            'rows' => $rows,
            'pagination' => $pagination,
            'empty_state' => [
                'title' => 'No users yet',
                'message' => 'Create the first account to start managing access.',
            ],
        ];
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     * @return list<array{label:string,href:string,active:bool}>
     */
    private function buildUserFilterItems(array $state, array $visibleColumns): array
    {
        $items = [];

        foreach ([
            ['label' => 'All', 'value' => 'all'],
            ['label' => 'Active', 'value' => 'active'],
            ['label' => 'Disabled', 'value' => 'disabled'],
            ['label' => 'Trashed', 'value' => 'trashed'],
        ] as $item) {
            $items[] = [
                'label' => $item['label'],
                'href' => $this->buildUsersUrl([
                    'q' => $state['query'],
                    'status' => $item['value'],
                    'sort' => $state['sort'],
                    'direction' => $state['direction'],
                    'columns' => $visibleColumns,
                ]),
                'active' => $state['filter'] === $item['value'],
            ];
        }

        return $items;
    }

    /**
     * @param list<string> $visibleColumns
     * @return list<array{label:string,key:string,checked:bool}>
     */
    private function buildColumnItems(array $visibleColumns): array
    {
        $items = [];

        foreach ($this->columnOptions() as $key => $label) {
            $items[] = [
                'label' => $label,
                'key' => $key,
                'checked' => in_array($key, $visibleColumns, true),
            ];
        }

        return $items;
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     * @return list<array<string, mixed>>
     */
    private function buildTableColumns(array $state, array $visibleColumns): array
    {
        $columns = [];
        $sortable = ['name', 'email', 'role', 'last_login'];

        foreach ($this->columnOptions() as $key => $label) {
            if (!in_array($key, $visibleColumns, true)) {
                continue;
            }

            $isSortable = in_array($key, $sortable, true);

            $columns[] = [
                'key' => $key,
                'label' => $label,
                'sortable' => $isSortable,
                'active' => $state['sort'] === $key,
                'href' => $isSortable ? $this->buildUsersUrl([
                    'q' => $state['query'],
                    'status' => $state['filter'],
                    'sort' => $key,
                    'direction' => $state['sort'] === $key && $state['direction'] === 'asc' ? 'desc' : 'asc',
                    'columns' => $visibleColumns,
                ]) : null,
                'sort_direction' => $state['sort'] === $key ? $state['direction'] : 'desc',
            ];
        }

        return $columns;
    }

    /**
     * @param list<User> $users
     * @return list<array<string, mixed>>
     */
    private function buildTableRows(array $users, int|string|null $protectedAdminId): array
    {
        $rows = [];

        foreach ($users as $user) {
            $role = $user->role();
            $isProtectedAdmin = $protectedAdminId !== null && (int) $user->getKey() === (int) $protectedAdminId;
            $isTrashed = !empty($user->getAttribute('deleted_at'));
            $isActiveSessionUser = $this->users->isActiveSessionUser($user, $this->auth);
            $bulkDisabled = $isProtectedAdmin || $isTrashed || $isActiveSessionUser;
            $bulkTitle = $isProtectedAdmin
                ? 'The last admin user cannot be selected for bulk actions.'
                : ($isTrashed
                    ? 'Trashed users cannot be selected for bulk actions.'
                    : ($isActiveSessionUser
                        ? 'The active session user cannot be selected for bulk actions.'
                        : 'Select user for bulk actions.'));
            $row = [
                'bulk' => [
                    'id' => (string) $user->getKey(),
                    'disabled' => $bulkDisabled,
                    'title' => $bulkTitle,
                    'label' => 'Select ' . (string) $user->getAttribute('name'),
                ],
                'cells' => [
                    'name' => [
                        'type' => 'avatar_link',
                        'value' => (string) $user->getAttribute('name'),
                        'href' => '/admin/users/' . $user->getKey(),
                        'avatar' => (string) $user->getAttribute('name'),
                        'meta' => 'ID ' . (string) $user->getKey(),
                    ],
                    'email' => [
                        'type' => 'text',
                        'value' => (string) $user->getAttribute('email'),
                    ],
                    'role' => [
                        'type' => 'badge',
                        'value' => $role === null ? 'Unknown' : (string) $role->getAttribute('name'),
                        'tone' => 'accent',
                    ],
                    'status' => [
                        'type' => 'badge_stack',
                        'items' => $this->buildStatusBadges($user, $isProtectedAdmin),
                    ],
                    'last_login' => [
                        'type' => 'text',
                        'value' => (string) ($user->getAttribute('last_login_at') ?: 'Never'),
                        'muted' => true,
                    ],
                ],
                'actions' => $this->buildRowActions($user, $isTrashed, $isProtectedAdmin),
            ];

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return list<array{value:string,tone:string,icon?:string}>
     */
    private function buildStatusBadges(User $user, bool $isProtectedAdmin): array
    {
        $badges = [];

        if (!empty($user->getAttribute('deleted_at'))) {
            $badges[] = [
                'value' => 'Trashed',
                'tone' => 'danger',
                'icon' => 'trash-2',
            ];
        } elseif ((bool) $user->getAttribute('is_active')) {
            $badges[] = [
                'value' => 'Active',
                'tone' => 'success',
            ];
        } else {
            $badges[] = [
                'value' => 'Disabled',
                'tone' => 'warning',
            ];
        }

        if ($isProtectedAdmin) {
            $badges[] = [
                'value' => 'Protected',
                'tone' => 'warning',
                'icon' => 'shield',
            ];
        }

        return $badges;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildRowActions(User $user, bool $isTrashed, bool $isProtectedAdmin): array
    {
        $actions = [
            [
                'type' => 'link',
                'label' => 'Profile',
                'href' => '/admin/users/' . $user->getKey(),
                'variant' => 'ghost',
            ],
        ];

        if ($isTrashed) {
            $actions[] = [
                'type' => 'form_button',
                'label' => 'Restore',
                'action' => '/admin/users/' . $user->getKey() . '/restore',
                'icon' => 'rotate-ccw',
                'variant' => 'secondary',
                'permission' => 'users.restore',
            ];
            $actions[] = [
                'type' => 'button',
                'label' => 'Delete',
                'icon' => 'trash-2',
                'variant' => 'danger',
                'disabled' => true,
                'title' => 'Restored users can be edited or deleted after restore.',
            ];

            return $actions;
        }

        $actions[] = [
            'type' => 'link',
            'label' => 'Edit',
            'href' => '/admin/users/' . $user->getKey() . '/edit',
            'variant' => 'secondary',
            'permission' => 'users.edit',
        ];

        if ($isProtectedAdmin) {
            $actions[] = [
                'type' => 'button',
                'label' => 'Delete',
                'icon' => 'trash-2',
                'variant' => 'danger',
                'disabled' => true,
                'title' => 'The last admin user cannot be deleted.',
            ];

            return $actions;
        }

        $actions[] = [
            'type' => 'form_button',
            'label' => 'Delete',
            'action' => '/admin/users/' . $user->getKey() . '/delete',
            'icon' => 'trash-2',
            'variant' => 'danger',
            'permission' => 'users.delete',
            'confirm' => 'Delete this user?',
        ];

        return $actions;
    }

    /**
     * @param array<string, string|int|list<string>> $params
     * @param string $path
     */
    private function buildUsersUrl(array $params, string $path = '/admin/users'): string
    {
        return $path . '?' . http_build_query($params);
    }

    /**
     * @param array<string, scalar|list<string>|null> $params
     * @param list<string> $visibleColumns
     * @return list<array{name:string,value:string}>
     */
    private function hiddenFields(array $params, array $visibleColumns = []): array
    {
        $fields = [];

        foreach ($params as $name => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $fields[] = [
                'name' => $name,
                'value' => (string) $value,
            ];
        }

        foreach ($visibleColumns as $column) {
            $fields[] = [
                'name' => 'columns[]',
                'value' => $column,
            ];
        }

        return $fields;
    }

    /**
     * @param list<User> $users
     * @param list<string> $columns
     */
    private function buildUsersCsv(array $users, array $columns): string
    {
        $labels = $this->columnOptions();
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return '';
        }

        fputcsv($handle, array_map(static fn (string $column): string => $labels[$column] ?? $column, $columns));

        foreach ($users as $user) {
            $row = [];
            foreach ($columns as $column) {
                $row[] = $this->exportColumnValue($user, $column);
            }

            fputcsv($handle, $row);
        }

        rewind($handle);

        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    private function exportColumnValue(User $user, string $column): string
    {
        return match ($column) {
            'name' => (string) $user->getAttribute('name'),
            'email' => (string) $user->getAttribute('email'),
            'role' => $this->exportRoleLabel($user),
            'status' => $this->exportStatusLabel($user),
            'last_login' => (string) ($user->getAttribute('last_login_at') ?: 'Never'),
            default => '',
        };
    }

    private function exportRoleLabel(User $user): string
    {
        $role = $user->role();

        return $role === null ? 'Unknown' : (string) $role->getAttribute('name');
    }

    private function exportStatusLabel(User $user): string
    {
        if (!empty($user->getAttribute('deleted_at'))) {
            return 'Trashed';
        }

        return (bool) $user->getAttribute('is_active') ? 'Active' : 'Disabled';
    }
}
