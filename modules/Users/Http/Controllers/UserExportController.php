<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Users\Support\UserExporter;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UserExportController extends Controller
{
    public function __construct(
        private readonly UserExporter $exporter,
    ) {}

    public function csv(ServerRequestInterface $request): ResponseInterface
    {
        return $this->exporter->csv($request);
    }

    public function pdf(ServerRequestInterface $request): ResponseInterface
    {
        return $this->exporter->pdf($request);
    }
}
