<?php

declare(strict_types=1);

namespace App\Support\Export\Pdf;

use App\Support\Export\Column;

final class TableHtmlBuilder
{
    /**
     * @param iterable<mixed> $rows
     * @param list<Column> $columns
     */
    public function build(iterable $rows, array $columns, string $title = ''): string
    {
        $rowsArray = $this->materialize($rows);

        $head = $this->renderHead($columns);
        $body = $this->renderBody($rowsArray, $columns);
        $titleHtml = $title !== '' ? '<h1>' . htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h1>' : '';

        return $this->wrapDocument($titleHtml . $head . $body);
    }

    /**
     * @param list<Column> $columns
     */
    private function renderHead(array $columns): string
    {
        $cells = '';
        foreach ($columns as $column) {
            $cells .= '<th>' . htmlspecialchars($column->label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</th>';
        }

        return '<table><thead><tr>' . $cells . '</tr></thead><tbody>';
    }

    /**
     * @param list<mixed> $rows
     * @param list<Column> $columns
     */
    private function renderBody(array $rows, array $columns): string
    {
        if ($rows === []) {
            return '<tr><td colspan="' . count($columns) . '">No data</td></tr></tbody></table>';
        }

        $html = '';
        foreach ($rows as $row) {
            $cells = '';
            foreach ($columns as $column) {
                $value = $column->resolve($row);
                $cells .= '<td>' . htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td>';
            }
            $html .= '<tr>' . $cells . '</tr>';
        }

        return $html . '</tbody></table>';
    }

    private function wrapDocument(string $body): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 18mm 14mm; }
    body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 9pt; color: #111; }
    h1 { font-size: 14pt; margin: 0 0 10pt 0; }
    table { width: 100%; border-collapse: collapse; page-break-inside: auto; }
    thead { display: table-header-group; }
    thead th { background: #1f2937; color: #fff; text-align: left; padding: 5pt 7pt; border: 1pt solid #1f2937; }
    tbody td { padding: 4pt 7pt; border: 0.5pt solid #d1d5db; vertical-align: top; }
    tbody tr { page-break-inside: auto; page-break-after: auto; }
    tbody tr:nth-child(even) td { background: #f9fafb; }
</style>
</head>
<body>
{$body}
</body>
</html>
HTML;
    }

    /**
     * @param iterable<mixed> $rows
     * @return list<mixed>
     */
    private function materialize(iterable $rows): array
    {
        $list = [];
        foreach ($rows as $row) {
            $list[] = $row;
        }

        return $list;
    }
}
