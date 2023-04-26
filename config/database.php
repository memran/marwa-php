<?php
return [
      'default'=>
        [
          'driver' => "mysql",
          'host' => env('DB_HOST'),
          'port' => env('DB_PORT'),
          'database' => env('DB_NAME'),
          'username' => env('DB_USER'),
          'password' => env('DB_PASSWORD',''),
          'charset' => "utf8mb4"
        ]
];


  ?>
