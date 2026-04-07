<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use App\Controllers\AdminController;
use Marwa\Framework\Facades\Router;

Router::get('/', [HomeController::class, 'index'])->name('home')->register();
Router::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard')->register();
