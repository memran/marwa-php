<?php

declare(strict_types=1);

return [
    'name' => 'admin',
    'slug' => 'admin',
    'version' => '1.0.0',
    'type' => 'admin',
    'meta' => [
        'label' => 'Admin Default',
        'description' => 'Minimal admin theme package for Marwa PHP.',
        'version' => '1.0.0',
        'author' => 'Marwa PHP',
        'tags' => [
            'admin',
            'theme',
        ],
    ],
    'layouts' => [
        'admin' => 'layouts/admin.twig',
        'auth' => 'layouts/auth.twig',
        'blank' => 'layouts/blank.twig',
    ],
    'assets' => [
        'css' => [
            'css/variables.css',
            'css/layout.css',
            'css/components.css',
        ],
        'js' => [
            'js/theme.js',
        ],
    ],
    'assets_url' => '/themes/admin',
    'views_path' => '.',
];
