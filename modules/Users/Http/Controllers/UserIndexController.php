<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Users\Support\UserIndexPage;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class UserIndexController extends Controller
{
    public function __construct(
        private readonly UserIndexPage $indexPage,
    ) {}

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $notice = $this->session('users.notice');
        session()->forget('users.notice');

        return $this->view('@users/index', $this->indexPage->viewData($request, [
            'users.notice' => is_string($notice) && $notice !== '' ? $notice : null,
        ]));
    }
}
