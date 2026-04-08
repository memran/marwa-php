<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use Psr\Http\Message\ResponseInterface;

final class DashboardController extends Controller
{
    public function index(): ResponseInterface
    {
        return $this->view('dashboard/index');
    }
}
