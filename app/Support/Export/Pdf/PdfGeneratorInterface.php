<?php

declare(strict_types=1);

namespace App\Support\Export\Pdf;

use Psr\Http\Message\ResponseInterface;

interface PdfGeneratorInterface
{
    public function html(string $html): self;

    /**
     * @param array<string, mixed> $options
     */
    public function options(array $options): self;

    public function save(string $path): void;

    public function binary(): string;

    public function download(string $filename): ResponseInterface;

    public function stream(string $filename): ResponseInterface;
}
