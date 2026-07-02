<?php

declare(strict_types=1);

namespace App\Modules\Security\Http\Controllers;

use App\Modules\Security\Support\SecurityRiskReport;
use Marwa\Framework\Controllers\Controller;
use Marwa\Router\Http\Input;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SecurityRiskReportController extends Controller
{
    public function __construct(
        private readonly SecurityRiskReport $report,
    ) {}

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        Input::setRequest($request);

        return $this->view('@security/risk', $this->report->viewData(Input::query('since_hours', 24)));
    }
}
