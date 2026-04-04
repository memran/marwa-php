<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use Marwa\Framework\Facades\Router;

Router::get('/', [HomeController::class, 'index'])->name('home')->register();
Router::post('/switch-theme', [HomeController::class, 'switchTheme'])->register();
