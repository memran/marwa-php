<?php

declare(strict_types=1);

use Marwa\Framework\Facades\Router;

Router::get('/', static fn () => view('welcome'))->name('home')->register();
