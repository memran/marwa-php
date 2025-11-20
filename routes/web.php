<?php

use Carbon\Carbon;
use Marwa\Framework\Facades\{Router, Config};
use Marwa\Router\Response;

Router::get('/web', fn() => Response::json(['hello' => 'marwa']))->register();
Router::get('/', function () {
    // //$key = Config::get('app.key');
    // $time = Carbon::now();
    // $body = "<h1>Welcome to MarwaPHP</h1>.
    //     <br> 
    //     Current Time is Now: {$time}
    //     <hr>
    // ";
    // return Response::html($body);
    return view("welcome");
})->name('hello')->register();

Router::get('/test', function () {
    dd("it works");
    return Response::json(['msg' => 'Ok!!']);
})->name('test')->register();

Router::get('/home', function () {
    return view('home/index', ['csrf' => bin2hex(random_bytes(16))]);
})->name('home')->register();
