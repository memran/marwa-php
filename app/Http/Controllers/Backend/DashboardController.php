<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Modules\DashboardStatus\DashboardStatusCards;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;

final class DashboardController extends Controller
{
    public function index(): ResponseInterface
    {
        /** @var DashboardStatusCards $statusCards */
        $statusCards = app(DashboardStatusCards::class);

        return $this->view('dashboard/index', [
            'status_cards' => $statusCards->cards(),
        ]);
    }
}
