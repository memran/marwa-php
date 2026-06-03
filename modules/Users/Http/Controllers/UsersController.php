<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Auth\Support\AuthManager;
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

    public function profile(): ResponseInterface
    {
        return $this->view('@users/profile', [
            'authUser' => $this->auth->user(),
        ]);
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

        return $this->view('@users/index', [
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

        return $this->view('@users/show', [
            'user' => $user,
            'protected_admin_id' => $this->users->protectedAdminId(),
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
