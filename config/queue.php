<?php
return [
 	'connection' =>
 	[
 		'driver' => 'mysql', // alternative redis
 		'db_host'=> 'localhost',
 		'db_name' => 'test',
 		'db_user' => 'root',
 		'db_pass' => '1234'
 	],
 	'db_pull_timer' => 10,
 	'process_heartbeat'=> 1,
 	'max_attempts' => 5

];
