<?php

declare(strict_types=1);

namespace App\Support\Export\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;
use Laminas\Diactoros\Response as HttpResponse;
use Laminas\Diactoros\Stream;
use Marwa\Router\Response;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final class DompdfGenerator implements PdfGeneratorInterface
{
    private string $html = '';
    private bool $loaded = false;
    private ?Dompdf $instance = null;

    /** @var array<string, mixed> */
    private array $options = [];

    public function __construct(private readonly ?Dompdf $injected = null)
    {
    }

    public function html(string $html): self
    {
        $this->html = $html;
        $this->loaded = false;
        return $this;
    }

    public function options(array $options): self
    {
        $this->options = $options;
        $this->loaded = false;
        return $this;
    }

    public function save(string $path): void
    {
        $binary = $this->binary();
        $written = @file_put_contents($path, $binary);
        if ($written === false) {
            throw new RuntimeException("Failed to write PDF to {$path}");
        }
    }

    public function binary(): string
    {
        $this->ensureRendered();
        return $this->instance()->output();
    }

    public function download(string $filename): ResponseInterface
    {
        $tmp = $this->writeTemp();
        return Response::download($tmp, $filename, ['Content-Type' => 'application/pdf']);
    }

    public function stream(string $filename): ResponseInterface
    {
        $tmp = $this->writeTemp();
        $resource = fopen($tmp, 'rb');
        if ($resource === false) {
            throw new RuntimeException("Failed to open temp PDF: {$tmp}");
        }
        $body = new Stream($resource);
        return new HttpResponse($body, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . addcslashes($filename, "\"\\") . '"',
        ]);
    }

    private function ensureRendered(): void
    {
        if ($this->loaded) {
            return;
        }
        $dompdf = $this->instance();
        if ($this->options !== []) {
            $dompdf->setOptions($this->buildOptions($this->options));
        }
        $dompdf->loadHtml($this->html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();
        $this->loaded = true;
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function buildOptions(array $overrides): Options
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        foreach ($overrides as $key => $value) {
            $options->set($key, $value);
        }
        return $options;
    }

    private function instance(): Dompdf
    {
        if ($this->injected !== null) {
            return $this->injected;
        }
        if ($this->instance === null) {
            $created = new Dompdf();
            $created->setOptions($this->buildOptions([]));
            $this->instance = $created;
        }
        return $this->instance;
    }

    private function writeTemp(): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'pdf_') . '.pdf';
        $this->save($tmp);
        register_shutdown_function(static function () use ($tmp): void {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
        });
        return $tmp;
    }
}
