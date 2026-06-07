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

if (!function_exists('pagination_view_data')) {
    /**
     * Build standard pagination summary and links for admin lists.
     *
     * @param array{total:int,per_page:int,current_page:int,last_page:int} $pagination
     * @param array<string, scalar|list<string>|null> $params
     * @return array{
     *     summary:string,
     *     links:list<array{page:int,url:string,active:bool}>
     * }
     */
    function pagination_view_data(array $pagination, string $path, array $params = [], string $pageParam = 'page'): array
    {
        return (new \App\Support\Pagination())->viewData($pagination, $path, $params, $pageParam);
    }
}
