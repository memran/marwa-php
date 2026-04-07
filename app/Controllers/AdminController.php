<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;

final class AdminController extends BackendController
{
    public function index(): ResponseInterface
    {
        return $this->renderBackend('home/index', [
            'title' => 'Admin dashboard',
            'subtitle' => 'Dedicated admin route',
        ]);
    }
}
