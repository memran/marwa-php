<?php

declare(strict_types=1);

$workerNum = function_exists('swoole_cpu_num') ? max(1, swoole_cpu_num() * 2) : 1;
$swooleMode = defined('SWOOLE_PROCESS') ? SWOOLE_PROCESS : 3;
$swooleSocket = defined('SWOOLE_SOCK_TCP') ? SWOOLE_SOCK_TCP : 1;
$logLevel = defined('SWOOLE_LOG_WARNING') ? SWOOLE_LOG_WARNING : 3;
$isDevelopment = in_array((string) env('APP_ENV', 'production'), ['local', 'development'], true);

return [
      'swoole' => [
            'host' => env('SWOOLE_HOST', '0.0.0.0'),
            'port' => env('SWOOLE_PORT', 9501),
            'mode' => $swooleMode,
            'sock_type' => $swooleSocket,
            'options' => [
                  // Production-ish defaults; override per env
                  'worker_num' => env('SWOOLE_WORKER_NUM', $workerNum),
                  'max_request' => env('SWOOLE_MAX_REQUEST', 10000),
                  'max_wait_time' => 3,
                  'max_coroutine' => 100000,
                  'daemonize' => env('SWOOLE_DAEMONIZE', false),
                  'log_level' => $logLevel,
                  'enable_coroutine' => env('SWOOLE_ENABLE_COROUTINE', true),
                  'http_compression' => env('SWOOLE_HTTP_COMPRESSION', true),
                  'buffer_output_size' => env('SWOOLE_BUFFER_OUTPUT_SIZE', 2 * 1024 * 1024),
                  'package_max_length' => env('SWOOLE_PACKAGE_MAX_LENGTH', 10 * 1024 * 1024),
                  // 'log_file' => __DIR__ . '/../storage/logs/swoole.log',
            ],
      ],
      'app' => [
            'debug' => $isDevelopment,
      ],
];
