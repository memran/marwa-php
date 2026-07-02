<?php

declare(strict_types=1);

namespace App\Modules\DatabaseManager\Http\Controllers;

use App\Modules\Activity\Events\ActivityRecordingRequested;
use App\Modules\DatabaseManager\Support\RawSqlExecutor;
use App\Modules\DatabaseManager\Support\SqlQueryGuard;
use App\Support\Pagination\PaginationResult;
use Marwa\Framework\Controllers\Controller;
use Marwa\Router\Http\Input;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DatabaseManagerController extends Controller
{
    public function __construct(
        private readonly RawSqlExecutor $executor,
        private readonly SqlQueryGuard $guard,
    ) {}

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isEnabled()) {
            return $this->response('Not found.', 404);
        }

        Input::setRequest($request);

        $query = trim((string) session('database_manager.query', ''));
        $page = max(1, (int) Input::query('page', 1));
        $result = null;
        $error = null;

        if ($query !== '') {
            try {
                $result = $this->executor->execute($query, $page);
            } catch (\Throwable $exception) {
                $error = $exception->getMessage();
            }
        }

        return $this->view('@database_manager/index', $this->viewData($query, $result, $error));
    }

    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->isEnabled()) {
            return $this->response('Not found.', 404);
        }

        Input::setRequest($request);

        $query = trim((string) Input::post('query', ''));
        $confirmed = (bool) Input::post('confirm_destructive', false);
        $page = 1;

        try {
            $sanitized = $this->guard->sanitize($query);

            if ($this->guard->requiresConfirmation($sanitized['normalized']) && !$confirmed) {
                return $this->view('@database_manager/index', $this->viewData(
                    $sanitized['query'],
                    null,
                    'This query can modify or destroy data. Tick the confirmation checkbox before executing it.'
                ));
            }

            $preview = $this->executor->execute($sanitized['query'], $page);
            event(new ActivityRecordingRequested(
                'database.executed',
                'Executed database query.',
                'database',
                null,
                ['state' => ['page' => $page, 'query' => $sanitized['normalized']]]
            ));

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
        return [
            'query' => $query,
            'result' => $result,
            'error' => $error,
            'pagination' => $result !== null && ($result['is_result_set'] ?? false)
                ? PaginationResult::fromArray([
                    'data' => $result['rows'] ?? [],
                    'total' => (int) ($result['total_rows'] ?? 0),
                    'per_page' => (int) ($result['per_page'] ?? per_page(25)),
                    'current_page' => (int) ($result['current_page'] ?? 1),
                    'last_page' => (int) ($result['last_page'] ?? 1),
                ], '/admin/database')
                : null,
        ];
    }

    private function isEnabled(): bool
    {
        return (bool) config(
            'settings.lifecycle.app.database_manager_enabled',
            !in_array((string) config('settings.lifecycle.app.env', config('app.env', 'production')), ['production', 'staging'], true)
        );
    }
}
