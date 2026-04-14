<?php

declare(strict_types=1);

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