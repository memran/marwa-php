<?php

declare(strict_types=1);

namespace App\Modules\DatabaseManager\Http\Controllers;

use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use App\Modules\DatabaseManager\Support\RawSqlExecutor;
use App\Modules\DatabaseManager\Support\SqlQueryGuard;

final class DatabaseManagerController extends Controller
{
    public function __construct()
    {
    }

    public function index(): ResponseInterface
    {
        if (!$this->isEnabled()) {
            return $this->response('Not found.', 404);
        }

        $query = trim((string) session('database_manager.query', ''));
        $page = max(1, (int) request('page', 1));
        $result = null;
        $error = null;

        if ($query !== '') {
            try {
                $result = app(RawSqlExecutor::class)->execute($query, $page);
            } catch (\Throwable $exception) {
                $error = $exception->getMessage();
            }
        }

        return $this->view('@database_manager/index', $this->viewData($query, $result, $error));
    }

    public function execute(): ResponseInterface
    {
        if (!$this->isEnabled()) {
            return $this->response('Not found.', 404);
        }

        $query = trim((string) request('query', ''));
        $confirmed = (bool) request('confirm_destructive', false);
        $page = 1;

        try {
            /** @var RawSqlExecutor $executor */
            $executor = app(RawSqlExecutor::class);
            /** @var SqlQueryGuard $guard */
            $guard = app(SqlQueryGuard::class);
            $sanitized = $guard->sanitize($query);

            if ($guard->requiresConfirmation($sanitized['normalized']) && !$confirmed) {
                return $this->view('@database_manager/index', $this->viewData(
                    $sanitized['query'],
                    null,
                    'This query can modify or destroy data. Tick the confirmation checkbox before executing it.'
                ));
            }

            $preview = $executor->execute($sanitized['query'], $page);

            session()->set('database_manager.query', $sanitized['query']);

            return $this->view('@database_manager/index', $this->viewData($preview['query'], $preview));
        } catch (\Throwable $exception) {
            return $this->view('@database_manager/index', $this->viewData($query, null, $exception->getMessage()));
        }
    }

    /**
     * @param array<string, mixed>|null $result
     * @return array<string, mixed>
     */
    private function viewData(string $query, ?array $result, ?string $error = null): array
    {
        /** @var \App\Support\AdminPagination $pagination */
        $pagination = app(\App\Support\AdminPagination::class);

        return [
            'query' => $query,
            'result' => $result,
            'error' => $error,
            'pagination' => $result !== null && ($result['is_result_set'] ?? false)
                ? $pagination->viewData([
                    'total' => (int) ($result['total_rows'] ?? 0),
                    'per_page' => (int) ($result['per_page'] ?? 25),
                    'current_page' => (int) ($result['current_page'] ?? 1),
                    'last_page' => (int) ($result['last_page'] ?? 1),
                ], '/admin/database')
                : null,
        ];
    }

    private function isEnabled(): bool
    {
        return (bool) env(
            'DATABASE_MANAGER_ENABLED',
            !in_array((string) env('APP_ENV', 'production'), ['production', 'staging'], true)
        );
    }
}
