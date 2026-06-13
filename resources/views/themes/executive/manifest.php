<?php

declare(strict_types=1);

return [
    'name' => 'executive',
    'slug' => 'executive',
    'version' => '1.0.0',
    'type' => 'admin',
    'parent' => 'admin',
    'meta' => [
        'label' => 'Executive',
        'description' => 'Executive admin theme package for Marwa PHP.',
        'version' => '1.0.0',
        'author' => 'Marwa PHP',
        'tags' => [
            'admin',
            'theme',
            'executive',
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
    'assets_url' => '/themes/executive',
    'views_path' => '.',
];
