<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

final class UserNotice
{
    public function pull(string $key = 'users.notice'): ?string
    {
        $notice = session($key);
        session()->forget($key);

        return is_string($notice) && $notice !== '' ? $notice : null;
    }

    /**
     * @param callable(int): string $successMessage
     */
    public function bulkResult(int $successCount, int $skippedCount, callable $successMessage): string
    {
        $parts = [];

        if ($successCount > 0) {
            $parts[] = $successMessage($successCount);
        }

        if ($skippedCount > 0) {
            $parts[] = $skippedCount . ' skipped (protected or not found).';
        }

        return implode(' ', $parts);
    }
}
