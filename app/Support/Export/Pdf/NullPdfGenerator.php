<?php

declare(strict_types=1);

namespace App\Support\Export\Pdf;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final class NullPdfGenerator implements PdfGeneratorInterface
{
    public function html(string $html): self
    {
        return $this;
    }

    public function options(array $options): self
    {
        return $this;
    }

    public function save(string $path): void
    {
        throw new RuntimeException('No PDF generator configured. Bind a real implementation to PdfGeneratorInterface.');
    }

    public function binary(): string
    {
        throw new RuntimeException('No PDF generator configured. Bind a real implementation to PdfGeneratorInterface.');
    }

    public function download(string $filename): ResponseInterface
    {
        throw new RuntimeException('No PDF generator configured. Bind a real implementation to PdfGeneratorInterface.');
    }

    public function stream(string $filename): ResponseInterface
    {
        throw new RuntimeException('No PDF generator configured. Bind a real implementation to PdfGeneratorInterface.');
    }
}
