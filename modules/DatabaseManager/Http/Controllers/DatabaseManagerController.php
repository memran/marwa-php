<?php

declare(strict_types=1);

namespace App\Modules\DatabaseManager\Http\Controllers;

use App\Support\AdminPagination;
use Marwa\Framework\Controllers\Controller;
use Marwa\Framework\Views\View;
use Psr\Http\Message\ResponseInterface;
use App\Modules\DatabaseManager\Support\RawSqlExecutor;
use App\Modules\DatabaseManager\Support\SqlQueryGuard;

final class DatabaseManagerController extends Controller
{
    public function index(): ResponseInterface
    {
        $this->ensureViewNamespace();

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

            return $this->view('@dbmanager/index', $this->viewData($query, $result, $error));
    }

    public function execute(): ResponseInterface
    {
        $this->ensureViewNamespace();

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
                return $this->view('@dbmanager/index', $this->viewData(
                    $sanitized['query'],
                    null,
                    'This query can modify or destroy data. Tick the confirmation checkbox before executing it.'
                ));
            }

            $preview = $executor->execute($sanitized['query'], $page);

            session()->set('database_manager.query', $sanitized['query']);

            return $this->view('@dbmanager/index', $this->viewData($preview['query'], $preview));
        } catch (\Throwable $exception) {
            return $this->view('@dbmanager/index', $this->viewData($query, null, $exception->getMessage()));
        }
    }

    /**
     * @param array<string, mixed>|null $result
     * @return array<string, mixed>
     */
    private function viewData(string $query, ?array $result, ?string $error = null): array
    {
        /** @var AdminPagination $pagination */
        $pagination = app(AdminPagination::class);

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

    private function ensureViewNamespace(): void
    {
        if (!app()->has(View::class)) {
            return;
        }

        app()->view()->addNamespace('dbmanager', dirname(__DIR__, 2) . '/resources/views');
    }
}
