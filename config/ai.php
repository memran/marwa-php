<?php

declare(strict_types=1);

return [
    'default' => 'openai',

    'providers' => [
        'openai' => [
            'api_key' => '',
            'model' => 'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free',
            'base_url' => 'https://openrouter.ai/api/v1/',
            'site_url' => 'https://marwa-php.dev',
            'app_name' => 'MarwaPHP',
        ],
        'ollama' => [
            'url' => 'http://localhost:11434',
            'model' => 'llama3',
        ],
        'anthropic' => [
            'api_key' => '',
            'model' => 'claude-3-opus',
        ],
    ],

    'options' => [
        'temperature' => 0.7,
        'max_tokens' => 2048,
        'timeout' => 60,
    ],
];
