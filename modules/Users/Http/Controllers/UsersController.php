<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Activity\Models\Activity;
use App\Modules\Auth\Support\AuthManager;
use App\Modules\Users\Models\User;
use App\Modules\Users\Support\UserDataTable;
use App\Modules\Users\Support\UserFormData;
use App\Modules\Users\Support\UserRepository;
use App\Modules\Users\Support\UserStatus;
use App\Support\AdminListState;
use App\Support\DataTable\DataTableView;
use App\Support\Pagination;
use Marwa\Framework\Controllers\Controller;
use Laminas\Diactoros\Response as HttpResponse;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UsersController extends Controller
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly UserRepository $users,
        private readonly UserFormData $forms,
        private readonly AdminListState $listState,
        private readonly UserDataTable $userTable,
        private readonly DataTableView $dataTable,
        private readonly Pagination $pagination,
    ) {}

    public function profile(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->auth->user();

        if ($user === null) {
            return $this->redirect('/admin/login');
        }

        $queryParams = $request->getQueryParams();
        $activityPage = max(1, (int) ($queryParams['activity_page'] ?? 1));
        $activityPageData = $this->recentActivities($user, $activityPage);

        return $this->view('@users/profile', [
            'authUser' => $user,
            'errors' => $this->session('errors', []),
            'old' => $this->session('_old_input', []),
            'default_tab' => (($queryParams['tab'] ?? '') === 'activity' || $activityPage > 1) ? 'activity' : 'overview',
            'activities' => $activityPageData['data'],
            'activity_total' => $activityPageData['pagination']['total'],
            'activity_pagination' => $this->pagination->viewData(
                $activityPageData['pagination'],
                '/admin/profile',
                [
                    'tab' => 'activity',
                ],
                'activity_page'
            ),
        ]);
    }

    public function updatePassword(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->auth->user();

        if ($user === null) {
            return $this->redirect('/admin/login');
        }

        $body = $request->getParsedBody();
        $input = is_array($body) ? $body : [];

        $currentPassword = trim((string) ($input['current_password'] ?? ''));
        $newPassword = trim((string) ($input['new_password'] ?? ''));
        $newPasswordConfirmation = trim((string) ($input['new_password_confirmation'] ?? ''));

        $errors = [];

        if ($currentPassword === '') {
            $errors['current_password'][] = 'Your current password is required.';
        } elseif (!password_verify($currentPassword, (string) $user->getPasswordHash())) {
            $errors['current_password'][] = 'The current password you entered is incorrect.';
        }

        if ($newPassword === '') {
            $errors['new_password'][] = 'The new password field is required.';
        } elseif (mb_strlen($newPassword) < 8) {
            $errors['new_password'][] = 'The new password must be at least 8 characters.';
        }

        if ($newPasswordConfirmation === '') {
            $errors['new_password_confirmation'][] = 'Please confirm your new password.';
        } elseif ($newPassword !== '' && $newPassword !== $newPasswordConfirmation) {
            $errors['new_password_confirmation'][] = 'The new password confirmation does not match.';
        }

        if ($errors !== []) {
            $this->withErrors($errors)->withInput($input);

            return $this->redirect('/admin/profile');
        }

        $user->setAttribute('password', password_hash($newPassword, PASSWORD_DEFAULT));
        $user->saveOrFail();

        $this->flash('users.notice', 'Password updated successfully.');

        return $this->redirect('/admin/profile');
    }

    public function index(): ResponseInterface
    {
        $state = $this->listState->state();
        $requestParams = $this->requestParams($state, request('columns', null));
        $status = UserStatus::tryFromFilter($state['filter']);

        $pageData = $this->users->paginatedUsers(
            $state['query'],
            $state['page'],
            null,
            $state['sort'],
            $state['direction'],
            $status
        );

        $pagination = $this->pagination->viewData(
            $pageData,
            '/admin/users',
            $this->paginationParams(
                $state,
                $this->dataTable->normalizeVisibleColumns($this->userTable, $requestParams['columns'] ?? null)
            )
        );

        $total = (int) User::query()->count();
        $stats = [
            'total' => $total,
            'active' => (int) User::query()->active()->count(),
            'disabled' => (int) User::query()->disabled()->count(),
            'trashed' => (int) User::withTrashed()->onlyTrashed()->count(),
        ];

        return $this->view('@users/index', [
            'stats' => $stats,
            'table' => $this->dataTable->build($this->userTable, $requestParams, $pageData, $pagination),
        ]);
    }

    public function create(): ResponseInterface
    {
        return $this->view('@users/form', $this->sharedFormViewData([
            'mode' => 'create',
            'title' => 'Create user',
            'action' => '/admin/users',
            'submit_label' => 'Create user',
            'user' => null,
        ]));
    }

    public function store(ServerRequestInterface $request): ResponseInterface
    {
        $payload = $this->forms->payload($request);
        $errors = $this->forms->validate($payload);

        if ($errors !== []) {
            $this->withErrors($errors)->withInput($payload);

            return $this->redirect('/admin/users/create');
        }

        $this->users->createUser($payload);
        $this->flash('users.notice', 'User created successfully.');

        return $this->redirect('/admin/users');
    }

    public function show(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->users->findById((int) ($vars['id'] ?? 0));

        if ($user === null) {
            return $this->redirect('/admin/users');
        }

        $queryParams = $request->getQueryParams();
        $activityPage = max(1, (int) ($queryParams['activity_page'] ?? 1));
        $activityPageData = $this->recentActivities($user, $activityPage);

        return $this->view('@users/show', [
            'user' => $user,
            'protected_admin_id' => $this->users->protectedAdminId(),
            'default_tab' => (($queryParams['tab'] ?? '') === 'activity' || $activityPage > 1) ? 'activity' : 'overview',
            'activities' => $activityPageData['data'],
            'activity_total' => $activityPageData['pagination']['total'],
            'activity_pagination' => $this->pagination->viewData(
                $activityPageData['pagination'],
                '/admin/users/' . $user->getKey(),
                ['tab' => 'activity'],
                'activity_page'
            ),
        ]);
    }

    public function exportCsv(): ResponseInterface
    {
        return $this->exportUsers('csv');
    }

    public function exportPdf(): ResponseInterface
    {
        return $this->exportUsers('pdf');
    }

    public function edit(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->users->findById((int) ($vars['id'] ?? 0));

        if ($user === null) {
            return $this->redirect('/admin/users');
        }

        return $this->view('@users/form', $this->sharedFormViewData([
            'mode' => 'edit',
            'title' => 'Edit user',
            'action' => '/admin/users/' . $user->getKey(),
            'submit_label' => 'Save changes',
            'user' => $user,
        ]));
    }

    public function update(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $id = (int) ($vars['id'] ?? 0);
        $user = $this->users->findById($id);

        if ($user === null) {
            return $this->redirect('/admin/users');
        }

        $payload = $this->forms->payload($request);
        $errors = $this->forms->validate($payload, $user);

        if ($errors !== []) {
            $this->withErrors($errors)->withInput($payload);

            return $this->redirect('/admin/users/' . $id . '/edit');
        }

        $this->users->updateUser($user, $payload, $payload['password'] !== '' ? $payload['password'] : null);
        $this->flash('users.notice', 'User updated successfully.');

        return $this->redirect('/admin/users');
    }

    public function destroy(ServerRequestInterface $request, array $vars = []): ResponseInterface
    {
        $user = $this->users->findById((int) ($vars['id'] ?? 0));

        if ($user === null) {
            return $this->redirect('/admin/users');
        }

        if ($this->users->isLastAdminUser($user)) {
            $this->flash('users.notice', 'The last admin user cannot be deleted.');

            return $this->redirect('/admin/users');
        }

        $this->users->deleteUser($user);
        $this->flash('users.notice', 'User deleted successfully.');

        return $this->redirect('/admin/users');
    }

    public function bulkDestroy(ServerRequestInterface $request): ResponseInterface
    {
        /** @var list<string> $ids */
        $ids = (array) ($request->getParsedBody()['ids'] ?? []);
        $deleted = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            $userId = (int) $id;
            if ($userId <= 0) {
                continue;
            }

            $user = $this->users->findById($userId);
            if ($user === null || $this->users->isLastAdminUser($user)) {
                $skipped++;
                continue;
            }

            $this->users->deleteUser($user);
            $deleted++;
        }

        $parts = [];
        if ($deleted > 0) {
            $parts[] = $deleted . ' user' . ($deleted !== 1 ? 's' : '') . ' deleted.';
        }
        if ($skipped > 0) {
            $parts[] = $skipped . ' skipped (protected or not found).';
        }

        $this->flash('users.notice', implode(' ', $parts));

        return $this->redirect('/admin/users');
    }

    public function bulkStatus(ServerRequestInterface $request): ResponseInterface
    {
        /** @var list<string> $ids */
        $ids = (array) ($request->getParsedBody()['ids'] ?? []);
        $status = strtolower(trim((string) ($request->getParsedBody()['bulk_status'] ?? '')));

        if (!in_array($status, ['active', 'disabled'], true)) {
            $this->flash('users.notice', 'Invalid status value.');
            return $this->redirect('/admin/users');
        }

        $isActive = $status === 'active' ? 1 : 0;
        $updated = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            $userId = (int) $id;
            if ($userId <= 0) {
                continue;
            }

            $user = $this->users->findById($userId);
            if ($user === null || $this->users->isLastAdminUser($user)) {
                $skipped++;
                continue;
            }

            $user->setAttribute('is_active', $isActive);
            $user->save();
            $updated++;
        }

        $parts = [];
        if ($updated > 0) {
            $parts[] = $updated . ' user' . ($updated !== 1 ? 's' : '') . ' set to ' . $status . '.';
        }
        if ($skipped > 0) {
            $parts[] = $skipped . ' skipped (protected or not found).';
        }

        $this->flash('users.notice', implode(' ', $parts));

        return $this->redirect('/admin/users');
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function sharedFormViewData(array $extra): array
    {
        $oldInput = $this->session('_old_input', []);
        $errors = $this->session('errors', []);

        return $this->forms->formViewData(
            $extra,
            is_array($oldInput) ? $oldInput : [],
            is_array($errors) ? $errors : []
        );
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     * @return array<string, scalar|list<string>|null>
     */
    private function paginationParams(array $state, array $visibleColumns): array
    {
        return array_filter([
            'q' => $state['query'],
            'filter' => $state['filter'],
            'sort' => $state['sort'],
            'direction' => $state['direction'],
            'columns' => $visibleColumns,
        ], static fn(mixed $value): bool => $value !== null && $value !== '' && $value !== []);
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @return array<string, mixed>
     */
    private function requestParams(array $state, mixed $columns): array
    {
        return [
            'q' => $state['query'],
            'filter' => $state['filter'],
            'sort' => $state['sort'],
            'direction' => $state['direction'],
            'page' => $state['page'],
            'columns' => $columns,
        ];
    }

    private function exportUsers(string $format): ResponseInterface
    {
        $state = $this->listState->state();
        $columns = $this->dataTable->normalizeVisibleColumns($this->userTable, request('columns', null));
        $status = UserStatus::tryFromFilter($state['filter']);
        $rows = $this->users->exportUsers($state['query'], $state['sort'], $state['direction'], $status);
        $filename = 'users-' . date('Ymd-His') . '.' . $format;

        if ($format === 'pdf') {
            return $this->downloadContent(
                $this->dataTable->buildPdf($this->userTable, $rows, $columns, $state),
                $filename,
                'application/pdf'
            );
        }

        return $this->downloadContent(
            $this->dataTable->buildCsv($this->userTable, $rows, $columns, $state),
            $filename,
            'text/csv; charset=UTF-8'
        );
    }

    /**
     * @return array{
     *     data:list<Activity>,
     *     pagination:array{total:int,per_page:int,current_page:int,last_page:int}
     * }
     */
    private function recentActivities(User $user, int $page = 1, int $perPage = 5): array
    {
        try {
            $builder = Activity::newQuery()->getBaseBuilder()
                ->where('actor_email', '=', $user->getAttribute('email'))
                ->orderBy('created_at', 'desc')
                ->paginate(max(1, $perPage), max(1, $page));
        } catch (\Throwable) {
            return [
                'data' => [],
                'pagination' => [
                    'total' => 0,
                    'per_page' => max(1, $perPage),
                    'current_page' => max(1, $page),
                    'last_page' => 1,
                ],
            ];
        }

        $rows = $builder['data'] ?? [];

        return [
            'data' => array_values(array_filter(array_map(
            static fn (array|object $row): Activity => Activity::newInstance(is_array($row) ? $row : (array) $row, true),
            is_array($rows) ? $rows : []
        ), static fn (Activity $activity): bool => $activity instanceof Activity)),
            'pagination' => [
                'total' => (int) ($builder['total'] ?? 0),
                'per_page' => (int) ($builder['per_page'] ?? max(1, $perPage)),
                'current_page' => (int) ($builder['current_page'] ?? max(1, $page)),
                'last_page' => (int) ($builder['last_page'] ?? 1),
            ],
        ];
    }

    private function downloadContent(string $content, string $filename, string $contentType): ResponseInterface
    {
        $stream = new Stream('php://temp', 'wb+');
        $stream->write($content);
        $stream->rewind();

        return new HttpResponse($stream, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . addcslashes($filename, '"\\') . '"',
        ]);
    }
}
