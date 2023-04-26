<?php

return [
		'register' => new \App\Listeners\RegisterListener,
		'onLogin' => new \App\Listeners\LoginListener,
		'onLogout' => new \App\Listeners\LogoutListener,
        //'saved' => new \App\Listeners\UserSaveListener,
];
