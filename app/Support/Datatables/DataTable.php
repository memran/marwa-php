<?php

declare(strict_types=1);

namespace App\Support\Datatables;

use Closure;
use App\Support\Datatables\Contracts\DataTableResultInterface;
use App\Support\Datatables\Exceptions\MissingQueryException;
use App\Support\Pagination\PaginationResult;
use Marwa\DB\ORM\QueryBuilder;
use Psr\Http\Message\ServerRequestInterface;

final class DataTable
{
    /**
     * @var list<Column>
     */
    private array $columns = [];

    /**
     * @var list<Filter>
     */
    private array $filters = [];

    /**
     * @var list<Action>
     */
    private array $actions = [];

    /**
     * @var list<BulkAction>
     */
    private array $bulkActions = [];

    /**
     * @var list<array<string, mixed>>
     */
    private array $exports = [];

    private ?QueryBuilder $query = null;
    private int $perPage = 15;
    private string $searchParameter = 'search';
    private string $sortParameter = 'sort';
    private string $directionParameter = 'direction';
    private string $pageParameter = 'page';
    private string $filterParameter = 'filters';
    private string $columnsParameter = 'columns';
    private string $selectedIdsParameter = 'ids';
    private string $path;
    private string $title = 'Records';
    private string $description = '';
    private string $searchPlaceholder = 'Search';
    private string $searchAriaLabel = 'Search records';
    /** @var array{title:string,message:string} */
    private array $emptyState = [
        'title' => 'No records',
        'message' => 'Nothing matched the current filters.',
    ];
    private string $rowKey = 'id';
    private string $defaultSortField = '';
    private string $defaultSortDirection = 'asc';
    private ?string $bulkDeleteUrl = null;
    private ?string $bulkStatusUrl = null;
    /** @var list<array{value:string,label:string}> */
    private array $bulkStatusOptions = [];
    /** @var null|Closure(array<string, mixed>|object, array<string, mixed>): array<string, mixed> */
    private ?Closure $rowCallback = null;

    private function __construct(private readonly ServerRequestInterface $request)
    {
        $this->path = rtrim($request->getUri()->getPath(), '/') ?: '/';
    }

    public static function fromRequest(ServerRequestInterface $request): self
    {
        return new self($request);
    }

    public function query(QueryBuilder $query): self
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @param list<Column> $columns
     */
    public function columns(array $columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * @param list<Filter> $filters
     */
    public function filters(array $filters): self
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * @param list<Action> $actions
     */
    public function actions(array $actions): self
    {
        $this->actions = $actions;

        return $this;
    }

    /**
     * @param list<BulkAction> $actions
     */
    public function bulkActions(array $actions): self
    {
        $this->bulkActions = $actions;

        return $this;
    }

    /**
     * @param list<array<string, mixed>> $exports
     */
    public function exports(array $exports): self
    {
        $this->exports = $exports;

        return $this;
    }

    public function paginate(int $perPage): self
    {
        $this->perPage = max(1, $perPage);

        return $this;
    }

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function searchPlaceholder(string $placeholder): self
    {
        $this->searchPlaceholder = $placeholder;

        return $this;
    }

    public function searchAriaLabel(string $label): self
    {
        $this->searchAriaLabel = $label;

        return $this;
    }

    /**
     * @param array{title:string,message:string} $emptyState
     */
    public function emptyState(array $emptyState): self
    {
        $this->emptyState = array_merge($this->emptyState, $emptyState);

        return $this;
    }

    public function path(string $path): self
    {
        $this->path = '/' . ltrim($path, '/');

        return $this;
    }

    public function rowKey(string $field): self
    {
        $this->rowKey = $field;

        return $this;
    }

    public function defaultSort(string $field, string $direction = 'asc'): self
    {
        $this->defaultSortField = $field;
        $this->defaultSortDirection = strtolower(trim($direction)) === 'desc' ? 'desc' : 'asc';

        return $this;
    }

    public function searchParameter(string $name): self
    {
        $this->searchParameter = $name;

        return $this;
    }

    public function sortParameter(string $name): self
    {
        $this->sortParameter = $name;

        return $this;
    }

    public function directionParameter(string $name): self
    {
        $this->directionParameter = $name;

        return $this;
    }

    public function pageParameter(string $name): self
    {
        $this->pageParameter = $name;

        return $this;
    }

    public function filterParameter(string $name): self
    {
        $this->filterParameter = $name;

        return $this;
    }

    public function columnsParameter(string $name): self
    {
        $this->columnsParameter = $name;

        return $this;
    }

    public function selectedIdsParameter(string $name): self
    {
        $this->selectedIdsParameter = $name;

        return $this;
    }

    public function bulkDeleteUrl(?string $url): self
    {
        $this->bulkDeleteUrl = $url !== null && trim($url) !== '' ? $url : null;

        return $this;
    }

    public function bulkStatusUrl(?string $url): self
    {
        $this->bulkStatusUrl = $url !== null && trim($url) !== '' ? $url : null;

        return $this;
    }

    /**
     * @param list<array{value:string,label:string}> $options
     */
    public function bulkStatusOptions(array $options): self
    {
        $this->bulkStatusOptions = $options;

        return $this;
    }

    /**
     * @param Closure(array<string, mixed>|object, array<string, mixed>): array<string, mixed> $callback
     */
    public function row(Closure $callback): self
    {
        $this->rowCallback = $callback;

        return $this;
    }

    /**
     * @return list<int>
     */
    public function selectedIds(): array
    {
        return $this->normalizeIds($this->inputValue($this->selectedIdsParameter, $this->request->getParsedBody()));
    }

    public function result(): DataTableResultInterface
    {
        $query = $this->query ?? throw new MissingQueryException('DataTable requires a query builder.');
        $state = $this->resolveState();
        $engine = new DataTableQuery(clone $query);

        $search = new Search($state['search']);
        $search->columns($this->searchableColumns());
        $engine->applySearch($search, $this->searchableColumns());
        $engine->applyFilters($this->filters, $state['filters']);
        $engine->applySort(
            new Sort($state['sort'], $state['direction']),
            $this->sortableColumnFields(),
            $this->defaultSortField,
            $this->defaultSortDirection
        );

        $pageData = $engine->paginate($this->perPage, $state['page']);
        $visibleColumns = $this->resolveVisibleColumns($state['columns']);

        $rows = $this->buildRows($pageData['data'], $visibleColumns);

        $payload = [
            'title' => $this->title,
            'description' => $this->description,
            'features' => $this->features(),
            'toolbar' => $this->toolbar($state, $visibleColumns),
            'bulk' => $this->bulk($state, $visibleColumns, $rows),
            'columns' => $this->resolveVisibleColumnMetadata($visibleColumns, $state),
            'rows' => $rows,
            'pagination' => PaginationResult::fromArray(
                $pageData,
                path: $this->path,
                query: $this->paginationQuery($state, $visibleColumns),
                pageName: $this->pageParameter
            ),
            'filters' => $this->filtersPayload($state),
            'search' => (new Search($state['search']))->toArray(),
            'sort' => (new Sort($state['sort'], $state['direction']))->toArray(),
            'actions' => $this->resolveToolbarActions(),
            'bulkActions' => $this->resolveBulkActionToolbar(),
            'empty_state' => $this->emptyState,
            'emptyState' => $this->emptyState,
        ];

        return new DataTableResult($payload);
    }

    /**
     * @return array<int, mixed>
     */
    public function exportRows(): array
    {
        $query = $this->query ?? throw new MissingQueryException('DataTable requires a query builder.');
        $state = $this->resolveState();
        $engine = new DataTableQuery(clone $query);

        $search = new Search($state['search']);
        $search->columns($this->searchableColumns());
        $engine->applySearch($search, $this->searchableColumns());
        $engine->applyFilters($this->filters, $state['filters']);
        $engine->applySort(
            new Sort($state['sort'], $state['direction']),
            $this->sortableColumnFields(),
            $this->defaultSortField,
            $this->defaultSortDirection
        );

        return $engine->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->result()->toArray();
    }

    /**
     * @param list<string> $visibleColumns
     * @param array{search:string,sort:string,direction:string,page:int,filters:array<string,mixed>,columns:list<string>} $state
     * @return list<array<string, mixed>>
     */
    private function resolveVisibleColumnMetadata(array $visibleColumns, array $state): array
    {
        $metadata = [];

        foreach ($this->columns as $column) {
            if (in_array($column->field(), $visibleColumns, true)) {
                $item = $column->toArray();
                $sortField = $column->sortField();

                if ($column->isSortable()) {
                    $isActive = $state['sort'] !== '' && $state['sort'] === $sortField;
                    $nextDirection = $isActive && $state['direction'] === 'asc' ? 'desc' : 'asc';

                    $item['href'] = $this->buildUrl(array_merge($state, [
                        'sort' => $sortField,
                        'direction' => $nextDirection,
                        'page' => 1,
                    ]), $visibleColumns);
                    $item['active'] = $isActive;
                    $item['sort_direction'] = $isActive ? $state['direction'] : $this->defaultSortDirection;
                }

                $metadata[] = $item;
            }
        }

        return $metadata;
    }

    /**
     * @param array<int, mixed> $rows
     * @param list<string> $visibleColumns
     * @return list<array<string, mixed>>
     */
    private function buildRows(array $rows, array $visibleColumns): array
    {
        $built = [];

        foreach ($rows as $row) {
            $built[] = $this->buildRow($row, $visibleColumns);
        }

        return $built;
    }

    /**
     * @param array<string, mixed>|object $row
     * @param list<string> $visibleColumns
     * @return array<string, mixed>
     */
    private function buildRow(array|object $row, array $visibleColumns): array
    {
        $cells = [];
        foreach ($this->columns as $column) {
            if (in_array($column->field(), $visibleColumns, true) && !$column->isHidden()) {
                $cells[$column->field()] = $column->toCell($row);
            }
        }

        $key = $this->rowKeyValue($row);

        $record = [
            'key' => $key,
            'bulk' => [
                'id' => $key,
                'disabled' => false,
                'label' => 'Select row',
                'title' => 'Select row',
            ],
            'cells' => $cells,
            'actions' => $this->resolveRowActions($row),
        ];

        if ($this->rowCallback !== null) {
            $overrides = ($this->rowCallback)($row, $record);
            if ($overrides !== []) {
                $record = array_replace_recursive($record, $overrides);
            }
        }

        return $record;
    }

    /**
     * @param array<string, mixed>|object $row
     */
    private function rowKeyValue(array|object $row): string
    {
        if (is_object($row) && method_exists($row, 'getKey')) {
            return (string) $row->getKey();
        }

        if (is_array($row)) {
            return (string) ($row[$this->rowKey] ?? '');
        }

        return '';
    }

    /**
     * @return list<Action>
     */
    /**
     * @param array<string, mixed>|object $row
     * @return list<array<string, mixed>>
     */
    private function resolveRowActions(array|object $row): array
    {
        $actions = [];

        foreach ($this->actions as $action) {
            $resolved = $action->resolve($row);
            if ($resolved !== null) {
                $actions[] = $resolved;
            }
        }

        return $actions;
    }

    /**
     * @return list<string>
     */
    private function searchableColumns(): array
    {
        $columns = [];

        foreach ($this->columns as $column) {
            if ($column->isSearchable()) {
                $columns[] = $column->field();
            }
        }

        return $columns;
    }

    /**
     * @return list<string>
     */
    private function sortableColumnFields(): array
    {
        $columns = [];

        foreach ($this->columns as $column) {
            if ($column->isSortable()) {
                $columns[] = $column->sortField();
            }
        }

        return $columns;
    }

    /**
     * @return list<string>
     */
    private function allColumnFields(): array
    {
        return array_map(static fn (Column $column): string => $column->field(), $this->columns);
    }

    /**
     * @param list<string>|null $requestedColumns
     * @return list<string>
     */
    private function resolveVisibleColumns(?array $requestedColumns): array
    {
        $allowed = $this->allColumnFields();

        if ($requestedColumns === null || $requestedColumns === []) {
            return $allowed;
        }

        $visible = [];
        foreach ($requestedColumns as $column) {
            if (in_array($column, $allowed, true) && !in_array($column, $visible, true)) {
                $visible[] = $column;
            }
        }

        return $visible === [] ? $allowed : $visible;
    }

    /**
     * @return array{search:string,sort:string,direction:string,page:int,filters:array<string,mixed>,columns:list<string>}
     */
    private function resolveState(): array
    {
        $input = $this->input();

        $search = trim((string) ($input[$this->searchParameter] ?? $input['q'] ?? ''));
        $sort = trim((string) ($input[$this->sortParameter] ?? ''));
        $direction = strtolower(trim((string) ($input[$this->directionParameter] ?? 'asc')));
        $page = (int) ($input[$this->pageParameter] ?? 1);

        if ($sort === '' && $this->defaultSortField !== '') {
            $sort = $this->defaultSortField;
        }

        if (!in_array($direction, ['asc', 'desc'], true)) {
            $direction = $this->defaultSortDirection;
        }

        $filters = $this->resolveFilters($input);
        $columns = $this->resolveRequestedColumns($input);

        return [
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
            'page' => max(1, $page),
            'filters' => $filters,
            'columns' => $columns,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function input(): array
    {
        $query = $this->request->getQueryParams();
        $body = $this->request->getParsedBody();

        if (!is_array($body)) {
            $body = [];
        }

        return array_replace_recursive($body, $query);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function resolveFilters(array $input): array
    {
        $filters = [];

        if (isset($input[$this->filterParameter]) && is_array($input[$this->filterParameter])) {
            foreach ($input[$this->filterParameter] as $key => $value) {
                if (is_string($key)) {
                    $filters[$key] = $value;
                }
            }
        }

        if ($filters === [] && isset($input['filter']) && count($this->filters) === 1) {
            $filters[$this->filters[0]->name()] = $input['filter'];
        }

        foreach ($this->filters as $filter) {
            if (!array_key_exists($filter->name(), $filters) && isset($input[$filter->name()])) {
                $filters[$filter->name()] = $input[$filter->name()];
            }
        }

        return $filters;
    }

    /**
     * @param array<string, mixed> $input
     * @return list<string>|null
     */
    private function resolveRequestedColumns(array $input): ?array
    {
        $requested = $input[$this->columnsParameter] ?? $input['columns'] ?? null;

        if (is_string($requested)) {
            $requested = array_filter(array_map('trim', explode(',', $requested)), static fn (string $value): bool => $value !== '');
        }

        if (!is_array($requested)) {
            return null;
        }

        $columns = [];
        foreach ($requested as $column) {
            if (is_string($column)) {
                $columns[] = $column;
            }
        }

        return $columns;
    }

    /**
     * @param array{search:string,sort:string,direction:string,page:int,filters:array<string,mixed>,columns:list<string>} $state
     * @param list<string> $visibleColumns
     * @return array<string, mixed>
     */
    private function toolbar(array $state, array $visibleColumns): array
    {
        $exports = $this->exportsToolbar($state, $visibleColumns);

        return [
            'search' => $this->searchToolbar($state, $visibleColumns),
            'filter' => $this->filterToolbar($state, $visibleColumns),
            'columns' => $this->columnsToolbar($state, $visibleColumns),
            'actions' => $this->resolveToolbarActions(),
            'exports' => $exports,
            'export_url' => $exports[0]['url'] ?? '',
        ];
    }

    /**
     * @param array{search:string,sort:string,direction:string,page:int,filters:array<string,mixed>,columns:list<string>} $state
     * @param list<string> $visibleColumns
     * @return array<string, mixed>
     */
    private function searchToolbar(array $state, array $visibleColumns): array
    {
        $search = new Search($state['search']);

        return [
            'action' => $this->path,
            'name' => $this->searchParameter,
            'value' => $search->termValue(),
            'placeholder' => $this->searchPlaceholder,
            'aria_label' => $this->searchAriaLabel,
            'submit_label' => 'Search',
            'clear_label' => 'Clear search',
            'clear_url' => $this->buildUrl(array_merge($state, ['search' => '', 'page' => 1]), $visibleColumns),
            'hidden_fields' => $this->hiddenFields([
                $this->sortParameter => $state['sort'],
                $this->directionParameter => $state['direction'],
                $this->filterParameter => $state['filters'],
                $this->columnsParameter => $visibleColumns,
            ], $visibleColumns),
        ];
    }

    /**
     * @param array{search:string,sort:string,direction:string,page:int,filters:array<string,mixed>,columns:list<string>} $state
     * @param list<string> $visibleColumns
     * @return array<string, mixed>
     */
    private function filterToolbar(array $state, array $visibleColumns): array
    {
        $items = [];
        foreach ($this->filters as $filter) {
            if (!$filter->isVisible()) {
                continue;
            }

            $value = $filter->extract($state['filters']);
            $options = $filter->optionsValue();

            if ($options !== []) {
                foreach ($options as $optionValue => $optionLabel) {
                    $items[] = [
                        'label' => $optionLabel,
                        'href' => $this->buildUrl(array_merge($state, [
                            'filters' => array_merge($state['filters'], [$filter->name() => $optionValue]),
                            'page' => 1,
                        ]), $visibleColumns),
                        'active' => (string) $value === (string) $optionValue,
                        'group' => $filter->labelValue(),
                    ];
                }

                continue;
            }

            $items[] = [
                'label' => $filter->labelValue(),
                'href' => $this->buildUrl(array_merge($state, [
                    'filters' => array_merge($state['filters'], [$filter->name() => $value]),
                    'page' => 1,
                ]), $visibleColumns),
                'active' => $filter->isActive($value),
                'group' => $filter->labelValue(),
            ];
        }

        return [
            'label' => 'Filters',
            'current_label' => $this->currentFilterLabel($state),
            'items' => $items,
        ];
    }

    /**
     * @param array{search:string,sort:string,direction:string,page:int,filters:array<string,mixed>,columns:list<string>} $state
     * @param list<string> $visibleColumns
     * @return array<string, mixed>
     */
    private function columnsToolbar(array $state, array $visibleColumns): array
    {
        $items = [];
        foreach ($this->columns as $column) {
            $items[] = [
                'label' => $column->labelText(),
                'key' => $column->field(),
                'checked' => in_array($column->field(), $visibleColumns, true),
            ];
        }

        return [
            'label' => 'Columns',
            'legend' => 'Visible columns',
            'visible_count' => count($visibleColumns),
            'action' => $this->path,
            'reset_url' => $this->buildUrl(array_merge($state, ['columns' => $this->allColumnFields()]), $this->allColumnFields()),
            'hidden_fields' => $this->hiddenFields([
                $this->searchParameter => $state['search'],
                $this->sortParameter => $state['sort'],
                $this->directionParameter => $state['direction'],
                $this->filterParameter => $state['filters'],
            ], $visibleColumns),
            'items' => $items,
            'submit_label' => 'Apply',
            'reset_label' => 'Reset',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function resolveToolbarActions(): array
    {
        $resolved = [];

        foreach ($this->actions as $action) {
            $item = $action->resolve();
            if ($item !== null && (($item['type'] ?? '') !== 'link' || isset($item['href']))) {
                $resolved[] = $item;
            }
        }

        return $resolved;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function resolveBulkActionToolbar(): array
    {
        $resolved = [];

        foreach ($this->bulkActions as $action) {
            $item = $action->resolve();
            if ($item !== null) {
                $resolved[] = $item;
            }
        }

        return $resolved;
    }

    /**
     * @param array<string, mixed> $state
     * @param list<string> $visibleColumns
     * @return list<array<string, mixed>>
     */
    private function exportsToolbar(array $state, array $visibleColumns): array
    {
        $resolved = [];

        foreach ($this->exports as $export) {
            $format = strtolower(trim((string) ($export['format'] ?? '')));
            if ($format === '') {
                continue;
            }

            $path = isset($export['url']) && is_string($export['url']) && $export['url'] !== ''
                ? $export['url']
                : rtrim($this->path, '/') . '/export/' . $format;

            $query = http_build_query([
                $this->searchParameter => $state['search'],
                $this->sortParameter => $state['sort'],
                $this->directionParameter => $state['direction'],
                $this->filterParameter => $this->buildFilterQueryValue($state['filters']),
                $this->columnsParameter => $visibleColumns,
            ]);

            $resolved[] = [
                'format' => $format,
                'label' => (string) ($export['label'] ?? strtoupper($format)),
                'title' => (string) ($export['title'] ?? ('Download as ' . strtoupper($format))),
                'icon' => (string) ($export['icon'] ?? 'download'),
                'url' => $query !== '' ? $path . '?' . $query : $path,
            ];
        }

        return $resolved;
    }

    /**
     * @param array{search:string,sort:string,direction:string,page:int,filters:array<string,mixed>,columns:list<string>} $state
     * @param list<string> $visibleColumns
     * @param list<array<string, mixed>> $rows
     * @return array<string, mixed>
     */
    private function bulk(array $state, array $visibleColumns, array $rows): array
    {
        return [
            'form_id' => 'datatable-bulk-form',
            'selectable_count' => count(array_filter($rows, static fn (array $row): bool => !($row['bulk']['disabled'] ?? false))),
            'select_all_label' => 'Select all',
            'selection_header_label' => 'Select rows',
            'status_placeholder' => 'Bulk status',
            'status_label' => 'Update status',
            'status_icon' => 'refresh-cw',
            'status_options' => $this->bulkStatusOptions,
            'delete_label' => 'Delete selected',
            'delete_icon' => 'trash-2',
            'delete_confirm' => 'Delete the selected rows?',
            'action_delete_url' => $this->bulkDeleteUrl ?? '',
            'action_status_url' => $this->bulkStatusUrl ?? '',
            'hidden_fields' => $this->hiddenFields([
                $this->searchParameter => $state['search'],
                $this->sortParameter => $state['sort'],
                $this->directionParameter => $state['direction'],
                $this->filterParameter => $state['filters'],
                $this->pageParameter => $state['page'],
            ], $visibleColumns),
        ];
    }

    /**
     * @param array{search:string,sort:string,direction:string,page:int,filters:array<string,mixed>,columns:list<string>} $state
     * @param list<string> $visibleColumns
     * @return array<string, mixed>
     */
    private function paginationQuery(array $state, array $visibleColumns): array
    {
        return [
            $this->searchParameter => $state['search'],
            $this->sortParameter => $state['sort'],
            $this->directionParameter => $state['direction'],
            $this->filterParameter => $this->buildFilterQueryValue($state['filters']),
            $this->columnsParameter => $visibleColumns,
        ];
    }

    /**
     * @param array<string, mixed> $state
     * @return array<int, array<string, mixed>>
     */
    private function filtersPayload(array $state): array
    {
        $filters = [];

        foreach ($this->filters as $filter) {
            $value = $filter->extract($state['filters']);
            $filters[] = $filter->toArray($value);
        }

        return $filters;
    }

    /**
     * @param array{search:string,sort:string,direction:string,page:int,filters:array<string,mixed>,columns:list<string>} $state
     */
    private function currentFilterLabel(array $state): string
    {
        foreach ($this->filters as $filter) {
            $value = $filter->extract($state['filters']);
            if ($filter->isActive($value)) {
                $options = $filter->optionsValue();
                if ($options !== [] && is_scalar($value) && isset($options[(string) $value])) {
                    return $options[(string) $value];
                }

                return $filter->labelValue();
            }
        }

        return 'All';
    }

    /**
     * @param array<string, mixed> $params
     * @param list<string> $visibleColumns
     * @return list<array{name:string,value:string}>
     */
    private function hiddenFields(array $params, array $visibleColumns): array
    {
        $fields = [];

        foreach ($params as $name => $value) {
            if ($name === $this->filterParameter && is_array($value)) {
                $value = $this->buildFilterQueryValue($value);
            }

            if ($value === null || $value === '') {
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $key => $nestedValue) {
                    if ($nestedValue === null || $nestedValue === '') {
                        continue;
                    }

                    if (is_array($nestedValue)) {
                        foreach ($nestedValue as $nestedKey => $nestedNestedValue) {
                            if ($nestedNestedValue !== null && $nestedNestedValue !== '') {
                                $fields[] = [
                                    'name' => $name . '[' . $key . '][' . $nestedKey . ']',
                                    'value' => (string) $nestedNestedValue,
                                ];
                            }
                        }

                        continue;
                    }

                    $fields[] = [
                        'name' => $name . '[' . $key . ']',
                        'value' => (string) $nestedValue,
                    ];
                }

                continue;
            }

            $fields[] = [
                'name' => (string) $name,
                'value' => (string) $value,
            ];
        }

        foreach ($visibleColumns as $column) {
            $fields[] = ['name' => $this->columnsParameter . '[]', 'value' => $column];
        }

        return $fields;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>|string|int|bool|null
     */
    private function buildFilterQueryValue(array $filters): array|string|int|bool|null
    {
        if ($this->filterParameter === 'filter' && count($this->filters) === 1) {
            $filter = $this->filters[0];

            return $filters[$filter->name()] ?? null;
        }

        return $filters;
    }

    /**
     * @param mixed $value
     * @return list<int>
     */
    private function normalizeIds(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $ids = [];
        foreach ($value as $item) {
            if (is_numeric($item)) {
                $id = (int) $item;
                if ($id > 0 && !in_array($id, $ids, true)) {
                    $ids[] = $id;
                }
            }
        }

        return $ids;
    }

    /**
     * @param array<string, mixed> $input
     * @return mixed
     */
    private function inputValue(string $key, array $input): mixed
    {
        return $input[$key] ?? null;
    }

    /**
     * @return array<string, bool>
     */
    private function features(): array
    {
        return [
            'search' => $this->searchableColumns() !== [],
            'filter' => $this->filters !== [],
            'columns' => count($this->columns) > 1,
            'export' => $this->exports !== [],
            'sort' => $this->sortableColumnFields() !== [],
            'pagination' => true,
            'actions' => $this->actions !== [],
            'bulk' => $this->bulkActions !== [],
        ];
    }

    /**
     * @param array<string, mixed> $state
     * @param list<string> $visibleColumns
     */
    private function buildUrl(array $state, array $visibleColumns): string
    {
        return $this->path . '?' . http_build_query([
            $this->searchParameter => $state['search'],
            $this->sortParameter => $state['sort'],
            $this->directionParameter => $state['direction'],
            $this->pageParameter => $state['page'],
            $this->filterParameter => $this->buildFilterQueryValue($state['filters']),
            $this->columnsParameter => $visibleColumns,
        ]);
    }
}
