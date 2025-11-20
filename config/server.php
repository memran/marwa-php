<?php

declare(strict_types=1);

return [
      'swoole' => [
            'host' => '0.0.0.0',
            'port' => 9501,
            'mode' => SWOOLE_PROCESS,
            'sock_type' => SWOOLE_SOCK_TCP,
            'options' => [
                  // Production-ish defaults; override per env
                  'worker_num' => swoole_cpu_num() * 2,
                  'max_request' => 10000,
                  'max_wait_time' => 3,
                  'max_coroutine' => 100000,
                  'daemonize' => 0, // 1 in real prod
                  'log_level' => SWOOLE_LOG_WARNING,
                  'enable_coroutine' => true,
                  'http_compression' => true,
                  'buffer_output_size' => 2 * 1024 * 1024,
                  'package_max_length' => 10 * 1024 * 1024,
                  // 'log_file' => __DIR__ . '/../storage/logs/swoole.log',
            ],
      ],
      'app' => [
            'debug' => true,
      ],
];
