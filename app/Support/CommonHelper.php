<?php

declare(strict_types=1);

if (!function_exists('is_mobile_request')) {
    /**
     * Mobile device detection helper.
     * Place in app/Support/ and autoload via composer.json
     */
    function is_mobile_request(): bool
    {
        if (!app()->has(\Psr\Http\Message\ServerRequestInterface::class)) {
            return false;
        }

        $request = app()->make(\Psr\Http\Message\ServerRequestInterface::class);
        $userAgent = $request->getHeaderLine('User-Agent');

        if (empty($userAgent)) {
            return false;
        }

        $mobileKeywords = [
            'mobile',
            'android',
            'iphone',
            'ipod',
            'ipad',
            'windows phone',
            'blackberry',
            'opera mini',
            'opera mobi',
            'nexus',
            'samsung',
            'huawei',
            'xiaomi',
        ];

        $userAgentLower = strtolower($userAgent);

        foreach ($mobileKeywords as $keyword) {
            if (strpos($userAgentLower, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('per_page')) {
    /**
     * Resolve the active pagination size from app settings or config.
     */
    function per_page(?int $fallback = null): int
    {
        return max(1, (int) (
            config('settings.lifecycle.pagination.default_per_page', config('pagination.default_per_page', $fallback ?? 10))
        ));
    }
}
