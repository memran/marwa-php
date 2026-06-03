<?php

declare(strict_types=1);

namespace App\Support\DataTable;

use App\Support\Export\CsvExporter;
use App\Support\Export\Exporter;
use App\Support\Export\Pdf\DompdfGenerator;
use App\Support\Export\Pdf\TableHtmlBuilder;
use App\Support\Export\PdfExporter;

final class DataTableView
{
    public function __construct(
        private readonly DataTableColumns $columns,
        private readonly DataTableToolbar $toolbar,
    ) {
    }

    /**
     * @param array<string, mixed> $requestParams
     * @param array{data:list<mixed>,total:int,per_page:int,current_page:int,last_page:int} $dataPage
     * @param array{summary:string,links:list<array{page:string,url:string,active:bool}>} $pagination
     * @return array<string, mixed>
     */
    public function build(
        DataTableConfigInterface $config,
        array $requestParams,
        array $dataPage,
        array $pagination
    ): array {
        $state = $this->resolveState($config, $requestParams);
        $visibleColumns = $this->normalizeVisibleColumns($config, $requestParams['columns'] ?? null);
        $buildUrl = $this->urlBuilder($config);
        $hiddenFields = fn (array $params, array $cols = []): array => $config->hiddenFields($params, $cols);

        $rows = $this->buildRows($config, $dataPage['data']);
        $toolbar = $this->buildToolbar($config, $state, $visibleColumns, $buildUrl, $hiddenFields, $rows);

        return $this->assembleSections($config, $state, $visibleColumns, $rows, $pagination, $buildUrl, $hiddenFields, $toolbar);
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     * @param iterable<mixed> $rows
     * @return string
     */
    public function buildCsv(
        DataTableConfigInterface $config,
        iterable $rows,
        array $visibleColumns,
        array $state
    ): string {
        $resolved = $this->resolveExportColumns($config, $visibleColumns);
        $exporter = $this->csvExporter();
        return $exporter->build($rows, $resolved, $this->exportTitle($config, $state));
    }

    /**
     * @param list<string> $visibleColumns
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param iterable<mixed> $rows
     */
    public function writeCsvToFile(
        DataTableConfigInterface $config,
        string $filePath,
        iterable $rows,
        array $visibleColumns,
        array $state
    ): void {
        file_put_contents($filePath, $this->buildCsv($config, $rows, $visibleColumns, $state));
    }

    /**
     * @param list<string> $visibleColumns
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param iterable<mixed> $rows
     */
    public function buildPdf(
        DataTableConfigInterface $config,
        iterable $rows,
        array $visibleColumns,
        array $state
    ): string {
        $resolved = $this->resolveExportColumns($config, $visibleColumns);
        $exporter = $this->pdfExporter();
        return $exporter->build($rows, $resolved, $this->exportTitle($config, $state));
    }

    /**
     * @param list<string> $visibleColumns
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param iterable<mixed> $rows
     */
    public function writePdfToFile(
        DataTableConfigInterface $config,
        string $filePath,
        iterable $rows,
        array $visibleColumns,
        array $state
    ): void {
        file_put_contents($filePath, $this->buildPdf($config, $rows, $visibleColumns, $state));
    }

    /**
     * @return callable(array{query:string,filter:string,sort:string,direction:string,page:int}, list<string>, ?string): string
     */
    public function urlBuilder(DataTableConfigInterface $config): callable
    {
        return function (array $state, array $visibleColumns = [], ?string $path = null) use ($config): string {
            return $this->buildUrl($config, $state, $visibleColumns, $path);
        };
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     */
    public function buildUsersUrl(
        DataTableConfigInterface $config,
        array $state,
        array $visibleColumns = [],
        ?string $path = null
    ): string {
        return $this->buildUrl($config, $state, $visibleColumns, $path);
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     */
    public function buildUrl(
        DataTableConfigInterface $config,
        array $state,
        array $visibleColumns = [],
        ?string $path = null
    ): string {
        $base = $path ?? $config->basePath();
        $params = $this->queryParams($config);

        return $base . '?' . http_build_query([
            $params['query'] => $state['query'],
            $params['filter'] => $state['filter'],
            $params['sort'] => $state['sort'],
            $params['direction'] => $state['direction'],
            'columns' => $visibleColumns,
        ]);
    }

    /**
     * @param array<string, mixed> $requestParams
     * @return array{query:string,filter:string,sort:string,direction:string,page:int}
     */
    public function resolveState(DataTableConfigInterface $config, array $requestParams): array
    {
        $params = $this->queryParams($config);
        $requestParams = array_merge([
            $params['query'] => '',
            $params['filter'] => $config->defaultFilter(),
            $params['sort'] => $config->defaultSort(),
            $params['direction'] => $config->defaultDirection(),
            $params['page'] => 1,
        ], $requestParams);

        return [
            'query' => (string) ($requestParams[$params['query']] ?? ''),
            'filter' => (string) ($requestParams[$params['filter']] ?? $config->defaultFilter()),
            'sort' => (string) ($requestParams[$params['sort']] ?? $config->defaultSort()),
            'direction' => (string) ($requestParams[$params['direction']] ?? $config->defaultDirection()),
            'page' => (int) ($requestParams[$params['page']] ?? 1),
        ];
    }

    /**
     * @param mixed $columns
     * @return list<string>
     */
    public function normalizeVisibleColumns(DataTableConfigInterface $config, mixed $columns): array
    {
        $allowed = array_keys($config->columnOptions());

        if (!is_array($columns)) {
            return $allowed;
        }

        $visible = [];
        foreach ($columns as $column) {
            if (is_string($column)
                && in_array($column, $allowed, true)
                && !in_array($column, $visible, true)
            ) {
                $visible[] = $column;
            }
        }

        return $visible === [] ? $allowed : $visible;
    }

    /**
     * @param list<mixed> $rows
     * @return list<array<string, mixed>>
     */
    private function buildRows(DataTableConfigInterface $config, array $rows): array
    {
        $built = [];
        foreach ($rows as $row) {
            $built[] = $config->buildRow($row);
        }
        return $built;
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     * @param list<array<string, mixed>> $rows
     * @return array<string, mixed>
     */
    private function buildToolbar(
        DataTableConfigInterface $config,
        array $state,
        array $visibleColumns,
        callable $buildUrl,
        callable $hiddenFields,
        array $rows
    ): array {
        $columnOptions = $config->columnOptions();
        $filterItems = $config->filterItems($state, $visibleColumns, $buildUrl);

        return [
            'search' => $this->toolbar->buildSearch(
                $config->basePath(),
                $state,
                $visibleColumns,
                $buildUrl,
                $hiddenFields,
                $config->searchPlaceholder(),
                $this->searchAriaLabel($config),
            ),
            'filter' => $this->toolbar->buildFilter($state, $visibleColumns, $buildUrl, $filterItems),
            'columns' => $this->toolbar->buildColumnsToolbar(
                $config->basePath(),
                $state,
                $visibleColumns,
                $columnOptions,
                $buildUrl,
                $hiddenFields,
            ),
            'exports' => $this->exportActions($config, $state, $visibleColumns, $buildUrl),
            'actions' => $this->toolbarActions($config),
        ];
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     * @return list<array{label:string,url:string,icon:string,format:string,variant:string}>
     */
    private function exportActions(
        DataTableConfigInterface $config,
        array $state,
        array $visibleColumns,
        callable $buildUrl
    ): array {
        $actions = [];
        foreach ($config->exports() as $export) {
            $path = $export['url'];
            $base = strtok($path, '?') ?: $config->basePath();
            $actions[] = [
                'label' => $export['label'],
                'url' => $buildUrl($state, $visibleColumns, $base),
                'icon' => $export['icon'],
                'format' => $export['format'],
                'variant' => $export['variant'],
            ];
        }
        return $actions;
    }

    /**
     * @return list<array<string, string>>
     */
    private function printAction(): array
    {
        return [[
            'type' => 'button',
            'label' => 'Print',
            'icon' => 'printer',
            'onclick' => 'window.print()',
            'title' => 'Print this page',
            'variant' => 'secondary',
        ]];
    }

    private function searchAriaLabel(DataTableConfigInterface $config): string
    {
        $base = $config->basePath();
        $resource = basename(rtrim($base, '/'));
        return 'Search ' . strtolower(rtrim($resource, 's'));
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<string> $visibleColumns
     * @param list<array<string, mixed>> $rows
     * @param array{summary:string,links:list<array{page:string,url:string,active:bool}>} $pagination
     * @param array{
     *     search:array<string, mixed>,
     *     filter:array<string, mixed>,
     *     columns:array<string, mixed>,
     *     exports:list<array{label:string,url:string,icon:string,format:string,variant:string}>,
     *     actions:list<array<string, string>>
     * } $toolbar
     * @return array<string, mixed>
     */
    private function assembleSections(
        DataTableConfigInterface $config,
        array $state,
        array $visibleColumns,
        array $rows,
        array $pagination,
        callable $buildUrl,
        callable $hiddenFields,
        array $toolbar
    ): array {
        $columnOptions = $config->columnOptions();
        $statusOptions = $config->statusOptions();

        return [
            'title' => $config->pageTitle(),
            'description' => $config->pageDescription(),
            'features' => $this->features($config),
            'toolbar' => $toolbar,
            'bulk' => $this->bulk($config, $state, $rows, $visibleColumns, $statusOptions, $hiddenFields),
            'columns' => $this->columns->build(
                $state,
                $visibleColumns,
                $columnOptions,
                $config->sortableKeys(),
                $buildUrl,
            ),
            'rows' => $rows,
            'pagination' => $pagination,
            'empty_state' => $this->emptyState($config),
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function features(DataTableConfigInterface $config): array
    {
        $defaults = [
            'search' => true,
            'filter' => true,
            'columns' => true,
            'export' => true,
            'sort' => true,
            'pagination' => true,
            'actions' => true,
            'bulk' => true,
        ];

        if ($config instanceof DataTableOptionsInterface) {
            return array_replace($defaults, $config->features());
        }

        return $defaults;
    }

    /**
     * @return array<string, string>
     */
    private function emptyState(DataTableConfigInterface $config): array
    {
        if ($config instanceof DataTableOptionsInterface) {
            return array_replace([
                'title' => 'No results',
                'message' => 'Adjust filters or add a new record to get started.',
            ], $config->emptyState());
        }

        return [
            'title' => 'No results',
            'message' => 'Adjust filters or add a new record to get started.',
        ];
    }

    private function bulkFormId(DataTableConfigInterface $config): string
    {
        return rtrim($config->basePath(), '/') . '-bulk-form';
    }

    /**
     * @return array<string, string>
     */
    private function bulkLabels(DataTableConfigInterface $config): array
    {
        $resource = basename(rtrim($config->basePath(), '/'));
        return [
            'delete_confirm' => 'Delete the selected ' . strtolower(rtrim($resource, 's')) . 's?',
        ];
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     * @param list<array<string, mixed>> $rows
     * @param list<string> $visibleColumns
     * @param list<array{value:string,label:string}> $statusOptions
     * @return array<string, mixed>
     */
    private function bulk(
        DataTableConfigInterface $config,
        array $state,
        array $rows,
        array $visibleColumns,
        array $statusOptions,
        callable $hiddenFields
    ): array {
        $deletePath = $config instanceof DataTableOptionsInterface ? $config->bulkDeletePath() : $config->basePath() . '/bulk-delete';
        $statusPath = $config instanceof DataTableOptionsInterface ? $config->bulkStatusPath() : $config->basePath() . '/bulk-status';

        if ($deletePath === null && $statusPath === null) {
            return [];
        }

        return $this->toolbar->buildBulk(
            $this->bulkFormId($config),
            $deletePath ?? '',
            $statusPath ?? '',
            $state,
            $rows,
            $visibleColumns,
            $statusOptions,
            $hiddenFields,
            $this->bulkLabels($config),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function toolbarActions(DataTableConfigInterface $config): array
    {
        if ($config instanceof DataTableOptionsInterface) {
            return $config->toolbarActions();
        }

        return $this->printAction();
    }

    /**
     * @return array{query:string,filter:string,sort:string,direction:string,page:string}
     */
    private function queryParams(DataTableConfigInterface $config): array
    {
        $defaults = [
            'query' => 'q',
            'filter' => 'status',
            'sort' => 'sort',
            'direction' => 'direction',
            'page' => 'page',
        ];

        if ($config instanceof DataTableOptionsInterface) {
            return array_replace($defaults, $config->queryParams());
        }

        return $defaults;
    }

    /**
     * @param list<string> $visibleKeys
     * @return list<\App\Support\Export\Column>
     */
    public function resolveExportColumns(DataTableConfigInterface $config, array $visibleKeys): array
    {
        return $config->resolveExportColumns($visibleKeys);
    }

    /**
     * @param array{query:string,filter:string,sort:string,direction:string,page:int} $state
     */
    private function exportTitle(DataTableConfigInterface $config, array $state): string
    {
        return ucfirst(str_replace('_', ' ', $state['filter'])) . ' export';
    }

    private function csvExporter(): Exporter
    {
        return new CsvExporter();
    }

    private function pdfExporter(): Exporter
    {
        return new PdfExporter(new DompdfGenerator(), new TableHtmlBuilder());
    }
}
