<?php

declare(strict_types=1);

namespace App\Modules\Queue\Support;

use App\Support\Datatables\DataTableResult;
use App\Support\Datatables\DTO\DataTableColumn;
use App\Support\Datatables\DTO\DataTableRow;
use App\Support\Pagination\PaginationResult;

final class QueueDataTable
{
    /**
     * @param list<array<string, mixed>> $jobs
     * @param array<string, mixed> $filters
     */
    public function make(array $jobs, int $page, int $perPage, string $search = '', array $filters = []): DataTableResult
    {
        $search = trim($search);
        $filters = $this->normalizeFilters($filters);
        $filteredJobs = $this->filterJobs($jobs, $search, $filters);
        $total = count($filteredJobs);
        $perPage = max(1, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;
        $visibleJobs = array_slice($filteredJobs, $offset, $perPage);

        return new DataTableResult(
            'Job Status',
            'Backed by the global queue.',
            [
                'search' => true,
                'filter' => true,
                'columns' => false,
                'export' => false,
                'bulk' => false,
                'actions' => true,
                'pagination' => true,
                'sort' => false,
            ],
            $this->toolbar($jobs, $search, $filters),
            [],
            $this->columns(),
            $this->rows($visibleJobs, $offset),
            PaginationResult::fromArray([
                'data' => $visibleJobs,
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $lastPage,
            ], '/admin/queue', $this->query($search, $filters)),
            ['items' => $this->filterItems($jobs, $search, $filters)],
            $this->searchPayload($search, $filters),
            ['field' => '', 'direction' => 'desc', 'active' => false],
            [],
            [],
            [
                'title' => 'No queue jobs found yet.',
                'message' => 'Trigger a queued email or job to see it here.',
            ],
        );
    }

    /**
     * @param list<array<string, mixed>> $jobs
     * @param array{status:string,queue:string} $filters
     * @return list<array<string, mixed>>
     */
    private function filterJobs(array $jobs, string $search, array $filters): array
    {
        return array_values(array_filter($jobs, function (array $job) use ($search, $filters): bool {
            if ($filters['status'] !== '' && (string) ($job['status'] ?? '') !== $filters['status']) {
                return false;
            }

            if ($filters['queue'] !== '' && (string) ($job['queue'] ?? 'default') !== $filters['queue']) {
                return false;
            }

            if ($search === '') {
                return true;
            }

            return str_contains($this->searchHaystack($job), mb_strtolower($search));
        }));
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{status:string,queue:string}
     */
    private function normalizeFilters(array $filters): array
    {
        $status = is_scalar($filters['status'] ?? null) ? strtolower(trim((string) $filters['status'])) : '';
        if (!in_array($status, ['pending', 'processing', 'completed', 'failed'], true)) {
            $status = '';
        }

        $queue = is_scalar($filters['queue'] ?? null) ? trim((string) $filters['queue']) : '';

        return [
            'status' => $status,
            'queue' => $queue,
        ];
    }

    /**
     * @return list<DataTableColumn>
     */
    private function columns(): array
    {
        return [
            DataTableColumn::fromArray(['key' => 'name', 'field' => 'name', 'label' => 'Job']),
            DataTableColumn::fromArray(['key' => 'queue', 'field' => 'queue', 'label' => 'Queue']),
            DataTableColumn::fromArray(['key' => 'status', 'field' => 'status', 'label' => 'Status']),
            DataTableColumn::fromArray(['key' => 'attempts', 'field' => 'attempts', 'label' => 'Attempts', 'align' => 'right']),
            DataTableColumn::fromArray(['key' => 'updated_at', 'field' => 'updated_at', 'label' => 'Updated']),
            DataTableColumn::fromArray(['key' => 'log', 'field' => 'log', 'label' => 'Log']),
        ];
    }

    /**
     * @param list<array<string, mixed>> $jobs
     * @return list<DataTableRow>
     */
    private function rows(array $jobs, int $offset): array
    {
        $rows = [];

        foreach ($jobs as $index => $job) {
            $jobId = (string) ($job['job_id'] ?? '');
            $status = (string) ($job['status'] ?? 'pending');
            $actions = [[
                'name' => 'open',
                'label' => 'Open',
                'href' => '/admin/queue/' . rawurlencode($jobId),
                'variant' => 'secondary',
                'type' => 'link',
            ]];

            if ($status === 'failed') {
                $actions[] = [
                    'name' => 'retry',
                    'label' => 'Retry',
                    'action' => '/admin/queue/' . rawurlencode($jobId) . '/retry',
                    'variant' => 'primary',
                    'type' => 'form_button',
                    'permission' => 'queue.retry',
                ];
            }

            $rows[] = DataTableRow::fromArray([
                'key' => $jobId !== '' ? $jobId : sha1((string) ($offset + $index)),
                'cells' => [
                    'name' => [
                        'type' => 'text',
                        'value' => (string) ($job['name'] ?? ''),
                        'href' => '/admin/queue/' . rawurlencode($jobId),
                    ],
                    'queue' => [
                        'type' => 'badge',
                        'value' => (string) ($job['queue'] ?? 'default'),
                        'tone' => 'muted',
                    ],
                    'status' => [
                        'type' => 'badge',
                        'value' => $status,
                        'tone' => $this->statusTone($status),
                    ],
                    'attempts' => [
                        'type' => 'text',
                        'value' => (string) ($job['attempts'] ?? 0),
                        'align' => 'right',
                    ],
                    'updated_at' => [
                        'type' => 'text',
                        'value' => (string) (($job['updated_at'] ?? null) ?: '-'),
                        'muted' => true,
                    ],
                    'log' => [
                        'type' => 'text',
                        'value' => (string) (($job['failure_reason'] ?? null) ?: 'No logs yet.'),
                        'muted' => empty($job['failure_reason']),
                    ],
                ],
                'actions' => $actions,
            ]);
        }

        return $rows;
    }

    private function statusTone(string $status): string
    {
        return match ($status) {
            'completed' => 'success',
            'processing' => 'accent',
            'failed' => 'danger',
            'pending' => 'warning',
            default => 'muted',
        };
    }

    /**
     * @param list<array<string, mixed>> $jobs
     * @param array{status:string,queue:string} $filters
     * @return array<string, mixed>
     */
    private function toolbar(array $jobs, string $search, array $filters): array
    {
        return [
            'search' => [
                'action' => '/admin/queue',
                'name' => 'q',
                'value' => $search,
                'placeholder' => 'Search queue jobs...',
                'aria_label' => 'Search queue jobs',
                'submit_label' => 'Search',
                'clear_label' => 'Clear search',
                'clear_url' => $this->url('', $filters, 1),
                'hidden_fields' => $this->hiddenFields($filters),
            ],
            'filter' => [
                'label' => 'Filters',
                'current_label' => $this->currentFilterLabel($filters),
                'items' => $this->filterItems($jobs, $search, $filters),
            ],
            'columns' => ['items' => []],
            'actions' => [],
        ];
    }

    /**
     * @param array{status:string,queue:string} $filters
     * @return array<string, mixed>
     */
    private function searchPayload(string $search, array $filters): array
    {
        return [
            'term' => $search,
            'columns' => ['name', 'job_id', 'queue', 'status', 'payload', 'log'],
            'active' => $search !== '',
            'clear_url' => $this->url('', $filters, 1),
        ];
    }

    /**
     * @param list<array<string, mixed>> $jobs
     * @param array{status:string,queue:string} $filters
     * @return list<array{label:string,href:string,active:bool,group:string}>
     */
    private function filterItems(array $jobs, string $search, array $filters): array
    {
        $items = [[
            'label' => 'All jobs',
            'href' => $this->url($search, ['status' => '', 'queue' => ''], 1),
            'active' => $filters['status'] === '' && $filters['queue'] === '',
            'group' => 'All',
        ]];

        foreach (['pending' => 'Pending', 'processing' => 'Processing', 'completed' => 'Completed', 'failed' => 'Failed'] as $value => $label) {
            $items[] = [
                'label' => $label,
                'href' => $this->url($search, ['status' => $value, 'queue' => $filters['queue']], 1),
                'active' => $filters['status'] === $value,
                'group' => 'Status',
            ];
        }

        foreach ($this->queues($jobs) as $queue) {
            $items[] = [
                'label' => 'Queue: ' . $queue,
                'href' => $this->url($search, ['status' => $filters['status'], 'queue' => $queue], 1),
                'active' => $filters['queue'] === $queue,
                'group' => 'Queue',
            ];
        }

        return $items;
    }

    /**
     * @param list<array<string, mixed>> $jobs
     * @return list<string>
     */
    private function queues(array $jobs): array
    {
        $queues = [];

        foreach ($jobs as $job) {
            $queue = trim((string) ($job['queue'] ?? 'default'));
            if ($queue !== '') {
                $queues[$queue] = $queue;
            }
        }

        ksort($queues, SORT_NATURAL | SORT_FLAG_CASE);

        return array_values($queues);
    }

    /**
     * @param array{status:string,queue:string} $filters
     * @return list<array{name:string,value:string}>
     */
    private function hiddenFields(array $filters): array
    {
        $fields = [];

        if ($filters['status'] !== '') {
            $fields[] = ['name' => 'filters[status]', 'value' => $filters['status']];
        }

        if ($filters['queue'] !== '') {
            $fields[] = ['name' => 'filters[queue]', 'value' => $filters['queue']];
        }

        return $fields;
    }

    /**
     * @param array{status:string,queue:string} $filters
     * @return array<string, mixed>
     */
    private function query(string $search, array $filters): array
    {
        $query = [];

        if ($search !== '') {
            $query['q'] = $search;
        }

        if ($filters['status'] !== '' || $filters['queue'] !== '') {
            $query['filters'] = array_filter($filters, static fn (string $value): bool => $value !== '');
        }

        return $query;
    }

    /**
     * @param array{status:string,queue:string} $filters
     */
    private function url(string $search, array $filters, int $page): string
    {
        $query = $this->query($search, $filters);
        if ($page > 1) {
            $query['page'] = $page;
        }

        return '/admin/queue' . ($query === [] ? '' : '?' . http_build_query($query));
    }

    /**
     * @param array{status:string,queue:string} $filters
     */
    private function currentFilterLabel(array $filters): string
    {
        $labels = [];

        if ($filters['status'] !== '') {
            $labels[] = ucfirst($filters['status']);
        }

        if ($filters['queue'] !== '') {
            $labels[] = $filters['queue'];
        }

        return $labels === [] ? 'All' : implode(' + ', $labels);
    }

    /**
     * @param array<string, mixed> $job
     */
    private function searchHaystack(array $job): string
    {
        $values = [
            $job['job_id'] ?? '',
            $job['name'] ?? '',
            $job['queue'] ?? '',
            $job['status'] ?? '',
            $job['attempts'] ?? '',
            $job['available_at'] ?? '',
            $job['reserved_at'] ?? '',
            $job['finished_at'] ?? '',
            $job['failed_at'] ?? '',
            $job['updated_at'] ?? '',
            $job['failure_reason'] ?? '',
            $job['payload_json'] ?? '',
        ];

        return mb_strtolower(implode(' ', array_map(static fn (mixed $value): string => (string) $value, $values)));
    }
}
