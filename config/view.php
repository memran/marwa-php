<?php

declare(strict_types=1);

return [
    'viewsPath' => resources_path('views'),
    'cachePath' => storage_path('cache/views'),
    'debug' => (bool) env('APP_DEBUG', false),
    'extension' => '.twig',
    'cache' => [
        'enabled' => false,
    ],
    'extensions' => [
        App\View\Extensions\SecurityViewExtension::class,
        App\View\Extensions\NavigationViewExtension::class,
        App\View\Extensions\UserAgentViewExtension::class,
        Marwa\View\Extension\AlpineExtension::class,
        Marwa\View\Extension\DateExtension::class,
        Marwa\View\Extension\HtmlExtension::class,
        Marwa\View\Extension\ImageExtension::class,
        Marwa\View\Extension\JsonExtension::class,
        Marwa\View\Extension\ListExtension::class,
        Marwa\View\Extension\MoneyExtension::class,
        Marwa\View\Extension\NumberExtension::class,
        Marwa\View\Extension\StatusExtension::class,
        Marwa\View\Extension\StringPresentationExtension::class,
        Marwa\View\Extension\TextExtension::class,
    ],
    // The following extensions need constructor arguments, so keep them
    // commented until the view config supports structured extension options.
    // Marwa\View\Extension\AssetExtension::class,
    // Marwa\View\Extension\IconExtension::class,
    // Marwa\View\Extension\MetaStackExtension::class,
    // Marwa\View\Extension\SeoExtension::class,
    // Marwa\View\Extension\TranslateExtension::class,
    // Marwa\View\Extension\UrlExtension::class,
    // Marwa\DebugBar\Extensions\TwigDumpExtension::class,
    'themePath' => resources_path('views/themes'),
    'activeTheme' => env('FRONTEND_THEME', 'default'),
    'fallbackTheme' => env('FRONTEND_THEME', 'default'),
    'adminTheme' => env('ADMIN_THEME', 'admin'),
];
