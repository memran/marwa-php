<?php

	use Marwa\Application\Facades\Router;
	use Marwa\Application\{Input,Response};
	use Marwa\Application\Utils\Str;
	use App\Middlewares\MarwaMiddleware;
	//use Marwa\Application\Routes\Router;
	Router::get('/', 'App\TestController::index');
	Router::get('/version', function(){
		$token = Str::password(10);
		return Response::json(['Version' => app()->version(), 'Token' => $token]);
	});

	Router::get('/{id}', function ($request, array $args) {
		dd($request->method());
		dd($request->all());
    	return Response::json('Welcome to Marwa Framework!'.Str::password(10));
	});

	Router::group('api', function () {
		Router::get('/api/{id}', function ($request, array $args) {
			
			return Response::json('Welcome to Marwa Framework!'.Str::password(10));
		});
	})->middleware(new MarwaMiddleware());

	// Router::get('/version', function ($request) {
    // 		dd('Welcome to Marwa Framework!');
	// });