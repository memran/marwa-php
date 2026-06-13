<?php

declare(strict_types=1);

return [
    'name' => 'Security Module',
    'slug' => 'security',
    'version' => '1.0.0',
    'providers' => [
        App\Modules\Security\SecurityServiceProvider::class,
    ],
    'paths' => [
        'views' => 'resources/views',
    ],
    'menu' => [
        'section' => 'Administration',
        'label' => 'Security',
        'route' => '/admin/security/risk',
        'order' => 35,
        'icon' => 'shield-alert',
    ],
];
