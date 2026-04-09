<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;

final class HomeController extends Controller
{
    public function index(): ResponseInterface
    {
        return $this->view('home/index');
    }
}
