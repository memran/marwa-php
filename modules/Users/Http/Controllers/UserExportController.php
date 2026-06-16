<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Users\Support\UserDataTable;
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
    ) {}

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
        $columns = $this->userTable->resolveExportColumns($request->getQueryParams()['columns'] ?? []);
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
