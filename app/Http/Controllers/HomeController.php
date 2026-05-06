<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;

final class HomeController extends Controller
{
    public function index(): ResponseInterface
    {
        return $this->view('home/index', [
            'admin_email' => trim((string) env('ADMIN_BOOTSTRAP_EMAIL', 'admin@marwa.test')),
            'admin_password' => (string) env('ADMIN_BOOTSTRAP_PASSWORD', 'ExampleAdminPassword123!'),
            'admin_login_url' => '/admin/login',
            'admin_dashboard_url' => '/admin/dashboard',
        ]);
    }
}
