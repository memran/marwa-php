<?php

	use Marwa\Application\Facades\Router;
	use Marwa\Application\{Input,Response};
	use Marwa\Application\Utils\Str;
	use Marwa\Application\Middlewares\AuthMiddleware;

	Router::get('/', 'App\TestController::index');
	Router::get('/version', function(){
		$token = Str::password(10);
		return Response::json(['Random String' => $token]);
	});