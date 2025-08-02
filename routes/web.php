<?php

	use Marwa\Application\Facades\Router;
	use Marwa\Application\Input;
	use Marwa\Application\Middlewares\AuthMiddleware;
	Router::get('/', 'App\TestController::index');
	Router::get('/version', function(){
		return 'Version: ' . config('app.version');
	});