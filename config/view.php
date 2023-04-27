<?php
return [
	'debug' => false,
	'cacheDir' => false,//private_storage().DS.'cache', // or set private_storage().DS.'cache'
	'expire' => 300,
	'globals'=>
		[
			'backend' =>'backend/'.env('ADMIN_THEME').'/',
			'frontend' =>'frontend/'.env('FRONT_THEME').'/',
			'ga_tracking'=> 'UA-2132123123123'
		]

];
