<?php
    return [
        'lifetime' => 3600,
        'segment' => 'Marwa\Application',
        'session_key' => env('APP_KEY'),
        'storage' => 'default' // default , file ,redis, mysql, memcache
    ];
