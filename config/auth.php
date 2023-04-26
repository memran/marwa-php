<?php
return [
    'default' =>[
        'adapter' => 'database',
        'token_expire' => 3600,
        'model' => 'App\Models\User',
        'loginUrl'=> '',
        'successUrl'=> 'admincp/dashboard'
    ],
    'jwtauth' =>[
	    'adapter' => 'jwt',
	    'token_expire' => 3600,
	    'model' => 'App\Models\Users',
	    'loginUrl'=> '/',
	    'successUrl'=> 'dashboard'
    ]

];
