<?php

	use Marwa\Application\Facades\Router;
	use Marwa\Application\{Input,Response};
	use Marwa\Application\Middlewares\AuthMiddleware;
	Router::get('/', 'App\TestController::index');
	Router::get('/version', function(){
		
		logger("Version endpoint accessed");

		return view('version', [
			'title' => 'Version',
			'version' => 1.0,
			'date' => date('Y-m-d H:i:s')
		]); 
	});