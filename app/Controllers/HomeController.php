<?php

declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use Marwa\Router\Response;
use Psr\Http\Message\RequestFactoryInterface;

final class HomeController
{

    public function index(): ResponseInterface
    {
        return Response::html('index');
    }
    public function edit(RequestFactoryInterface $request): ResponseInterface
    {
        return Response::html('index');
    }
    public function save(): ResponseInterface
    {
        return Response::html('index');
    }
    public function add(): ResponseInterface
    {
        return Response::html('index');
    }
}
