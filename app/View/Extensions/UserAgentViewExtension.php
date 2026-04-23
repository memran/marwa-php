<?php

declare(strict_types=1);

namespace App\View\Extensions;

use Marwa\Framework\Contracts\ViewExtensionInterface;
use Marwa\Framework\Views\Extension\AbstractViewExtension;

final class UserAgentViewExtension extends AbstractViewExtension implements ViewExtensionInterface
{
    private bool $registered = false;

    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        $this->addFilter('ua_summary', [$this, 'summarize']);
        $this->addFilter('ip_format', [$this, 'formatIp']);
        $this->registered = true;
    }

    public function summarize(string $value, int $maxLength = 72): string
    {
        $value = trim($value);

        if ($value === '') {
            return 'No browser info';
        }

        $summary = trim($this->browserName($value) . ' ' . $this->browserVersion($value) . $this->osLabel($value));
        $summary = preg_replace('/\s+/', ' ', $summary);
        $summary = trim((string) $summary);

        if ($summary === '' || $summary === 'Browser') {
            $summary = $value;
        }

        if (mb_strlen($summary) <= $maxLength) {
            return $summary;
        }

        $sliceLength = max(0, $maxLength - 1);

        return mb_substr($summary, 0, $sliceLength) . '…';
    }

    public function formatIp(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return 'Unknown IP';
        }

        if (filter_var($value, FILTER_VALIDATE_IP) === false) {
            return $value;
        }

        $packed = inet_pton($value);

        if ($packed === false) {
            return $value;
        }

        $normalized = inet_ntop($packed);

        return is_string($normalized) && $normalized !== '' ? $normalized : $value;
    }

    private function browserName(string $value): string
    {
        return match (true) {
            preg_match('/\bEdg(?:A|iOS)?\/\d+/i', $value) === 1 => 'Edge',
            preg_match('/\bOPR\/\d+/i', $value) === 1 => 'Opera',
            preg_match('/\bBrave\/\d+/i', $value) === 1 => 'Brave',
            preg_match('/\bFirefox\/\d+/i', $value) === 1 => 'Firefox',
            preg_match('/\bChrome\/\d+/i', $value) === 1 && preg_match('/\bEdg(?:A|iOS)?\/\d+|\bOPR\/\d+|\bBrave\/\d+/i', $value) !== 1 => 'Chrome',
            preg_match('/\bSafari\/\d+/i', $value) === 1 && preg_match('/\bChrome\/\d+|\bEdg(?:A|iOS)?\/\d+|\bOPR\/\d+/i', $value) !== 1 => 'Safari',
            default => 'Browser',
        };
    }

    private function browserVersion(string $value): string
    {
        if (preg_match('/\b(?:Edg(?:A|iOS)?|OPR|Brave|Firefox|Chrome|Version)\/([0-9.]+)/i', $value, $matches) !== 1) {
            return '';
        }

        return trim($matches[1]);
    }

    private function osLabel(string $value): string
    {
        return match (true) {
            str_contains($value, 'Windows NT 10.0') => ' on Windows 10',
            str_contains($value, 'Windows NT 11.0') => ' on Windows 11',
            str_contains($value, 'Windows NT 6.3') => ' on Windows 8.1',
            str_contains($value, 'Windows NT 6.2') => ' on Windows 8',
            str_contains($value, 'Windows NT 6.1') => ' on Windows 7',
            str_contains($value, 'Mac OS X') => ' on macOS',
            str_contains($value, 'Android') => ' on Android',
            str_contains($value, 'iPhone') || str_contains($value, 'iPad') => ' on iOS',
            str_contains($value, 'Linux') => ' on Linux',
            default => '',
        };
    }
}
