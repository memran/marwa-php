<?php
	return [
        'upload_tmp_dir' => ROOT.'storage/uploadfiles/',
        'http_parse_post' => 0,
        'enable_static_handler' => true,
        'document_root' => ROOT.'public',
        //'ssl_cert_file' => $ssl_dir . '/ssl.crt',
        //'ssl_key_file' => $ssl_dir . '/ssl.key',
        //'open_http2_protocol' => true, // Enable HTTP2 protocol //
        'log_file' => STORAGE.'logs/swoole.log',
        'log_level' => 5, /* 0 =>DEBUG // all the levels of log will be recorded
                  1 =>TRACE
                  2 =>INFO
                  3 =>NOTICE
                  4 =>WARNING
                  5 =>ERROR
                  */
        'dispatch_mode' => 1, /**Usage advice
                stateless server: 3 is advised for synchronous and blocking server, 1 is advised for asynchronous and non-blocking server
                stateful server: 2, 4, 5
                   1 => Polling
                   2 => Fixed Mode
                   3 => preemptive mode
                   4 => ip mode
                   5 => uid mode
                   ref: https://www.swoole.co.uk/docs/modules/swoole-server/configuration
                   */
        //'open_tcp_nodelay' => true,
        'worker_num' => 8,
        'task_worker_num' => 1,
        'max_request' => 100000,
        'pid_file' => '/var/run/swoole.pid',
        //'heartbeat_idle_time' => 600,
        //'heartbeat_check_interval' => 60
    ];
 ?>
