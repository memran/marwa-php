<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controller;
use App\Support\ThemeSwitcher;
use Marwa\Router\Http\Input;
use Marwa\Router\Response;
use Marwa\Support\Security;
use Psr\Http\Message\ResponseInterface;

final class HomeController extends Controller
{
    public function index(): ResponseInterface
    {
        app(ThemeSwitcher::class)->applyToView();

        return $this->render('welcome', [
            'csrf' => Security::csrfToken(),
        ]);
    }

    public function switchTheme(): ResponseInterface
    {
        if (!Security::verifyCsrf((string) Input::post('_token', ''))) {
            return Response::redirect('/', 303);
        }

        app(ThemeSwitcher::class)->persist((string) Input::post('theme_name', 'default'));

        return Response::redirect('/', 303);
    }
}
