<?php
	
	return [
		'file' => [
			'path' => private_storage() . DIRECTORY_SEPARATOR . 'cache',
			'expire' => 300,
		],
		'redis' => [
			'host' => '127.0.0.1',
			'port' => 6379
		],
		'memcached' =>
			[
				'host' => '127.0.0.1',
				'port' => 11211
			]
	];
