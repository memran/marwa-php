<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Users\Support\UserIndexPage;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;

final class UserIndexController extends Controller
{
    public function __construct(
        private readonly UserIndexPage $indexPage,
    ) {}

    public function index(): ResponseInterface
    {
        return $this->view('@users/index', $this->indexPage->viewData());
    }
}
