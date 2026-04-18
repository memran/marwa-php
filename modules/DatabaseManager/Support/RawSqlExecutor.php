<?php

declare(strict_types=1);

namespace App\Modules\DatabaseManager\Support;

use Marwa\DB\Connection\ConnectionManager;
use PDO;
use PDOStatement;

final class RawSqlExecutor
{
    public function __construct(
        private readonly ConnectionManager $manager,
        private readonly SqlQueryGuard $guard,
    ) {
    }

    /**
     * @return array{
     *     query:string,
     *     normalized_query:string,
     *     statement_type:string,
     *     requires_confirmation:bool,
     *     is_result_set:bool,
     *     columns:list<string>,
     *     rows:list<array<string, mixed>>,
     *     total_rows:int,
     *     affected_rows:int,
     *     current_page:int,
     *     per_page:int,
     *     last_page:int
     * }
     */
    public function execute(string $query, int $page = 1, ?int $perPage = null): array
    {
        $sanitized = $this->guard->sanitize($query);
        $query = $sanitized['query'];
        $normalized = $sanitized['normalized'];
        $page = max(1, $page);
        $perPage = max(1, min(100, (int) ($perPage ?? config('settings.lifecycle.pagination.default_per_page', 25))));

        $statement = $this->pdo()->prepare($normalized);

        if (!$statement instanceof PDOStatement) {
            throw new \RuntimeException('Unable to prepare the SQL statement.');
        }

        $statement->execute();

        $isResultSet = $statement->columnCount() > 0;
        $columns = $this->columns($statement);
        $allRows = $isResultSet ? $statement->fetchAll(PDO::FETCH_ASSOC) : [];
        $totalRows = count($allRows);
        $lastPage = max(1, (int) ceil(($totalRows === 0 ? 1 : $totalRows) / $perPage));
        $page = min($page, $lastPage);
        $offset = ($page - 1) * $perPage;

        return [
            'query' => $query,
            'normalized_query' => $normalized,
            'statement_type' => $this->guard->statementType($normalized),
            'requires_confirmation' => $this->guard->requiresConfirmation($normalized),
            'is_result_set' => $isResultSet,
            'columns' => $columns,
            'rows' => $isResultSet ? array_slice($allRows, $offset, $perPage) : [],
            'total_rows' => $totalRows,
            'affected_rows' => $isResultSet ? 0 : max(0, $statement->rowCount()),
            'current_page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
        ];
    }

    private function pdo(): PDO
    {
        return $this->manager->getPdo((string) config('database.default', 'sqlite'));
    }

    /**
     * @return list<string>
     */
    private function columns(PDOStatement $statement): array
    {
        $columns = [];
        $count = $statement->columnCount();

        for ($index = 0; $index < $count; $index++) {
            $meta = $statement->getColumnMeta($index);
            $name = is_array($meta) ? (string) ($meta['name'] ?? '') : '';
            $columns[] = $name !== '' ? $name : 'column_' . ($index + 1);
        }

        return $columns;
    }
}
