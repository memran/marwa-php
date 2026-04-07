<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;

final class HomeController extends FrontendController
{
    public function index(): ResponseInterface
    {
        return $this->renderFrontend('welcome');
    }
}
