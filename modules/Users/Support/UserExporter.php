<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Support\Export\CsvExporter;
use App\Support\Export\Pdf\DompdfGenerator;
use App\Support\Export\Pdf\TableHtmlBuilder;
use App\Support\Export\PdfExporter;
use Laminas\Diactoros\Response as HttpResponse;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UserExporter
{
    public function __construct(
        private readonly UserDataTable $userTable,
    ) {}

    public function csv(ServerRequestInterface $request): ResponseInterface
    {
        return $this->export($request, 'csv');
    }

    public function pdf(ServerRequestInterface $request): ResponseInterface
    {
        return $this->export($request, 'pdf');
    }

    private function export(ServerRequestInterface $request, string $format): ResponseInterface
    {
        $table = $this->userTable->make($request);
        $rows = $table->exportRows($this->exportLimit());
        $columns = $this->userTable->resolveExportColumns($request->getQueryParams()['columns'] ?? []);
        $filename = 'users-' . date('Ymd-His') . '.' . $format;

        $content = $format === 'pdf'
            ? $this->pdfExporter()->build($rows, $columns, 'User accounts')
            : (new CsvExporter())->build($rows, $columns, 'User accounts');

        return $this->downloadContent(
            $content,
            $filename,
            $format === 'pdf' ? 'application/pdf' : 'text/csv; charset=UTF-8'
        );
    }

    private function pdfExporter(): PdfExporter
    {
        return new PdfExporter(new DompdfGenerator(), new TableHtmlBuilder());
    }

    private function exportLimit(): int
    {
        return max(1, min(5000, (int) config('settings.lifecycle.security.user_export_limit', 1000)));
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
