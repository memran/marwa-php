<?php

declare(strict_types=1);

namespace App\Modules\Security\Support;

use App\Support\Datatables\DataTableResult;
use App\Support\Datatables\DTO\DataTableColumn;
use App\Support\Datatables\DTO\DataTableRow;
use App\Support\Pagination\PaginationResult;

final class SecurityRiskDataTable
{
    /**
     * @param list<array<string, mixed>> $entries
     * @param array<string, mixed> $filters
     */
    public function make(array $entries, int $page, int $perPage, int $sinceHours, string $search = '', array $filters = []): DataTableResult
    {
        $search = trim($search);
        $filters = $this->normalizeFilters($filters);
        $filteredEntries = $this->filterEntries($entries, $search, $filters);
        $total = count($filteredEntries);
        $perPage = max(1, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $offset = ($page - 1) * $perPage;
        $visibleEntries = array_slice($filteredEntries, $offset, $perPage);

        return new DataTableResult(
            'Latest signals',
            'Most recent entries from the framework security risk log.',
            [
                'search' => true,
                'filter' => true,
                'columns' => false,
                'export' => false,
                'bulk' => false,
                'actions' => false,
                'pagination' => true,
                'sort' => false,
            ],
            $this->toolbar($entries, $sinceHours, $search, $filters),
            [],
            $this->columns(),
            $this->rows($visibleEntries, $offset),
            PaginationResult::fromArray([
                'data' => $visibleEntries,
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $lastPage,
            ], '/admin/security/risk', $this->query($sinceHours, $search, $filters)),
            ['items' => $this->filterItems($entries, $sinceHours, $search, $filters)],
            $this->searchPayload($sinceHours, $search, $filters),
            $this->sortPayload(),
            [],
            [],
            [
                'title' => 'No risk data yet.',
                'message' => 'Generate traffic or record a signal to populate the report.',
            ],
        );
    }

    /**
     * @param list<array<string, mixed>> $entries
     * @param array{severity:string,category:string} $filters
     * @return list<array<string, mixed>>
     */
    private function filterEntries(array $entries, string $search, array $filters): array
    {
        return array_values(array_filter($entries, function (array $entry) use ($search, $filters): bool {
            if ($filters['severity'] !== '' && $this->severity($entry) !== $filters['severity']) {
                return false;
            }

            if ($filters['category'] !== '' && (string) ($entry['category'] ?? 'unknown') !== $filters['category']) {
                return false;
            }

            if ($search === '') {
                return true;
            }

            return str_contains($this->searchHaystack($entry), mb_strtolower($search));
        }));
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{severity:string,category:string}
     */
    private function normalizeFilters(array $filters): array
    {
        $severity = is_scalar($filters['severity'] ?? null) ? strtolower(trim((string) $filters['severity'])) : '';
        if (!in_array($severity, ['high', 'medium', 'low'], true)) {
            $severity = '';
        }

        $category = is_scalar($filters['category'] ?? null) ? trim((string) $filters['category']) : '';

        return [
            'severity' => $severity,
            'category' => $category,
        ];
    }

    /**
     * @param list<array<string, mixed>> $entries
     * @param array{severity:string,category:string} $filters
     * @return array<string, mixed>
     */
    private function toolbar(array $entries, int $sinceHours, string $search, array $filters): array
    {
        return [
            'search' => [
                'action' => '/admin/security/risk',
                'name' => 'q',
                'value' => $search,
                'placeholder' => 'Search signals...',
                'aria_label' => 'Search security risk signals',
                'submit_label' => 'Search',
                'clear_label' => 'Clear search',
                'clear_url' => $this->url($sinceHours, '', $filters, 1),
                'hidden_fields' => $this->hiddenFields($sinceHours, $filters),
            ],
            'filter' => [
                'label' => 'Filters',
                'current_label' => $this->currentFilterLabel($filters),
                'items' => $this->filterItems($entries, $sinceHours, $search, $filters),
            ],
            'columns' => ['items' => []],
            'actions' => [],
        ];
    }

    /**
     * @param array{severity:string,category:string} $filters
     * @return array<string, mixed>
     */
    private function searchPayload(int $sinceHours, string $search, array $filters): array
    {
        return [
            'term' => $search,
            'columns' => ['timestamp', 'category', 'score', 'message', 'context'],
            'active' => $search !== '',
            'clear_url' => $this->url($sinceHours, '', $filters, 1),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sortPayload(): array
    {
        return [
            'field' => '',
            'direction' => 'desc',
            'active' => false,
        ];
    }

    /**
     * @param list<array<string, mixed>> $entries
     * @param array{severity:string,category:string} $filters
     * @return list<array{label:string,href:string,active:bool,group:string}>
     */
    private function filterItems(array $entries, int $sinceHours, string $search, array $filters): array
    {
        $items = [[
            'label' => 'All signals',
            'href' => $this->url($sinceHours, $search, ['severity' => '', 'category' => ''], 1),
            'active' => $filters['severity'] === '' && $filters['category'] === '',
            'group' => 'All',
        ]];

        foreach (['high' => 'High severity', 'medium' => 'Medium severity', 'low' => 'Low severity'] as $value => $label) {
            $items[] = [
                'label' => $label,
                'href' => $this->url($sinceHours, $search, ['severity' => $value, 'category' => $filters['category']], 1),
                'active' => $filters['severity'] === $value,
                'group' => 'Severity',
            ];
        }

        foreach ($this->categories($entries) as $category) {
            $items[] = [
                'label' => 'Category: ' . $category,
                'href' => $this->url($sinceHours, $search, ['severity' => $filters['severity'], 'category' => $category], 1),
                'active' => $filters['category'] === $category,
                'group' => 'Category',
            ];
        }

        return $items;
    }

    /**
     * @param list<array<string, mixed>> $entries
     * @return list<string>
     */
    private function categories(array $entries): array
    {
        $categories = [];

        foreach ($entries as $entry) {
            $category = trim((string) ($entry['category'] ?? 'unknown'));
            if ($category !== '') {
                $categories[$category] = $category;
            }
        }

        ksort($categories, SORT_NATURAL | SORT_FLAG_CASE);

        return array_values($categories);
    }

    /**
     * @param array{severity:string,category:string} $filters
     * @return list<array{name:string,value:string}>
     */
    private function hiddenFields(int $sinceHours, array $filters): array
    {
        $fields = [['name' => 'since_hours', 'value' => (string) $sinceHours]];

        if ($filters['severity'] !== '') {
            $fields[] = ['name' => 'filters[severity]', 'value' => $filters['severity']];
        }

        if ($filters['category'] !== '') {
            $fields[] = ['name' => 'filters[category]', 'value' => $filters['category']];
        }

        return $fields;
    }

    /**
     * @param array{severity:string,category:string} $filters
     * @return array<string, mixed>
     */
    private function query(int $sinceHours, string $search, array $filters): array
    {
        $query = ['since_hours' => $sinceHours];

        if ($search !== '') {
            $query['q'] = $search;
        }

        if ($filters['severity'] !== '' || $filters['category'] !== '') {
            $query['filters'] = array_filter($filters, static fn (string $value): bool => $value !== '');
        }

        return $query;
    }

    /**
     * @param array{severity:string,category:string} $filters
     */
    private function url(int $sinceHours, string $search, array $filters, int $page): string
    {
        $query = $this->query($sinceHours, $search, $filters);
        if ($page > 1) {
            $query['page'] = $page;
        }

        return '/admin/security/risk' . ($query === [] ? '' : '?' . http_build_query($query));
    }

    /**
     * @param array{severity:string,category:string} $filters
     */
    private function currentFilterLabel(array $filters): string
    {
        $labels = [];

        if ($filters['severity'] !== '') {
            $labels[] = ucfirst($filters['severity']);
        }

        if ($filters['category'] !== '') {
            $labels[] = $filters['category'];
        }

        return $labels === [] ? 'All' : implode(' + ', $labels);
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function severity(array $entry): string
    {
        $score = (int) ($entry['score'] ?? 0);

        if ($score >= 80) {
            return 'high';
        }

        return $score >= 40 ? 'medium' : 'low';
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function searchHaystack(array $entry): string
    {
        $values = [
            $entry['timestamp'] ?? '',
            $entry['category'] ?? '',
            $entry['score'] ?? '',
            $entry['message'] ?? '',
            $entry['context_display'] ?? '',
        ];

        return mb_strtolower(implode(' ', array_map(static fn (mixed $value): string => (string) $value, $values)));
    }

    /**
     * @return list<DataTableColumn>
     */
    private function columns(): array
    {
        return [
            DataTableColumn::fromArray(['key' => 'timestamp', 'field' => 'timestamp', 'label' => 'Timestamp']),
            DataTableColumn::fromArray(['key' => 'category', 'field' => 'category', 'label' => 'Category']),
            DataTableColumn::fromArray(['key' => 'score', 'field' => 'score', 'label' => 'Score', 'align' => 'right']),
            DataTableColumn::fromArray(['key' => 'message', 'field' => 'message', 'label' => 'Message']),
            DataTableColumn::fromArray(['key' => 'context', 'field' => 'context', 'label' => 'Context']),
        ];
    }

    /**
     * @param list<array<string, mixed>> $entries
     * @return list<DataTableRow>
     */
    private function rows(array $entries, int $offset): array
    {
        $rows = [];

        foreach ($entries as $index => $entry) {
            $rows[] = DataTableRow::fromArray([
                'key' => $this->rowKey($entry, $offset + $index),
                'cells' => [
                    'timestamp' => [
                        'type' => 'text',
                        'value' => (string) ($entry['timestamp'] ?? ''),
                        'muted' => true,
                    ],
                    'category' => [
                        'type' => 'badge',
                        'value' => (string) ($entry['category'] ?? 'unknown'),
                        'tone' => 'accent',
                    ],
                    'score' => [
                        'type' => 'text',
                        'value' => (string) ($entry['score'] ?? 0),
                        'align' => 'right',
                    ],
                    'message' => [
                        'type' => 'text',
                        'value' => (string) ($entry['message'] ?? ''),
                    ],
                    'context' => [
                        'type' => 'text',
                        'value' => (string) ($entry['context_display'] ?? '{}'),
                        'muted' => true,
                    ],
                ],
            ]);
        }

        return $rows;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function rowKey(array $entry, int $index): string
    {
        $timestamp = trim((string) ($entry['timestamp'] ?? ''));
        $category = trim((string) ($entry['category'] ?? ''));

        return sha1($timestamp . '|' . $category . '|' . $index);
    }
}
