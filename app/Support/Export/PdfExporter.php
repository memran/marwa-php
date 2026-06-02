<?php

declare(strict_types=1);

namespace App\Support\Export;

use App\Support\Export\Pdf\PdfGeneratorInterface;
use App\Support\Export\Pdf\TableHtmlBuilder;

final class PdfExporter implements Exporter
{
    public function __construct(
        private readonly PdfGeneratorInterface $generator,
        private readonly TableHtmlBuilder $htmlBuilder,
    ) {
    }

    public function build(iterable $rows, array $columns, string $title = ''): string
    {
        $html = $this->htmlBuilder->build($rows, $columns, $title);
        return $this->generator->html($html)->binary();
    }

    public function contentType(): string
    {
        return 'application/pdf';
    }

    public function fileExtension(): string
    {
        return 'pdf';
    }

    public function fileBaseName(): string
    {
        return 'export';
    }
}
