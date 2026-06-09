<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Users\Support\UserDataTable;
use App\Support\Export\Column as ExportColumn;
use App\Support\Export\CsvExporter;
use App\Support\Export\Pdf\DompdfGenerator;
use App\Support\Export\Pdf\TableHtmlBuilder;
use App\Support\Export\PdfExporter;
use Laminas\Diactoros\Response as HttpResponse;
use Laminas\Diactoros\Stream;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UserExportController extends Controller
{
    public function __construct(
        private readonly UserDataTable $userTable,
    ) {
    }

    public function csv(ServerRequestInterface $request): ResponseInterface
    {
        return $this->export('csv', $request);
    }

    public function pdf(ServerRequestInterface $request): ResponseInterface
    {
        return $this->export('pdf', $request);
    }

    private function export(string $format, ServerRequestInterface $request): ResponseInterface
    {
        $table = $this->userTable->make($request);
        $rows = $table->exportRows();
        $columns = $this->resolveExportColumns($request);
        $filename = 'users-' . date('Ymd-His') . '.' . $format;
        $csvExporter = new CsvExporter();
        $pdfExporter = new PdfExporter(new DompdfGenerator(), new TableHtmlBuilder());
        $content = $format === 'pdf'
            ? $pdfExporter->build($rows, $columns, 'User accounts')
            : $csvExporter->build($rows, $columns, 'User accounts');

        return $this->downloadContent(
            $content,
            $filename,
            $format === 'pdf' ? 'application/pdf' : 'text/csv; charset=UTF-8'
        );
    }

    /**
     * @return list<ExportColumn>
     */
    private function resolveExportColumns(ServerRequestInterface $request): array
    {
        $requested = $request->getQueryParams()['columns'] ?? [];
        if (is_string($requested)) {
            $requested = array_filter(array_map('trim', explode(',', $requested)), static fn (string $value): bool => $value !== '');
        }

        if (!is_array($requested)) {
            $requested = [];
        }

        $allowed = [];
        foreach ($this->userTable->exportColumns() as $column) {
            $allowed[$column->key] = $column;
        }

        if ($requested === []) {
            return array_values($allowed);
        }

        $visible = [];
        foreach ($requested as $key) {
            if (is_string($key) && isset($allowed[$key])) {
                $visible[] = $allowed[$key];
            }
        }

        return $visible === [] ? array_values($allowed) : $visible;
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
