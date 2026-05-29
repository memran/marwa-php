<?php

declare(strict_types=1);

namespace App\Support;

final class AdminToast
{
    /**
     * @return list<array{tone:string,title:string,message:string,icon:string}>
     */
    public static function fromSession(): array
    {
        return self::fromSessionData(session()->all());
    }

    /**
     * @param array<string, mixed> $session
     * @return list<array{tone:string,title:string,message:string,icon:string}>
     */
    public static function fromSessionData(array $session): array
    {
        $toasts = [];

        foreach ($session as $key => $value) {
            if (!self::isToastKey((string) $key)) {
                continue;
            }

            $items = self::toastsForKey((string) $key, $value);

            foreach ($items as $item) {
                $toasts[] = $item;
            }
        }

        return $toasts;
    }

    /**
     * @return list<array{tone:string,title:string,message:string,icon:string}>
     */
    private static function toastsForKey(string $key, mixed $value): array
    {
        if (self::isErrorKey($key)) {
            $message = self::summaryMessage($value);

            if ($message === null) {
                return [];
            }

            return [[
                'tone' => 'error',
                'title' => 'Validation error',
                'message' => $message,
                'icon' => 'circle-x',
            ]];
        }

        $message = self::normalizeMessage($value);

        if ($message === null) {
            return [];
        }

        $tone = self::toneForMessage($key, $message);

        return [[
            'tone' => $tone,
            'title' => self::titleForTone($tone),
            'message' => $message,
            'icon' => self::iconForTone($tone),
        ]];
    }

    private static function isToastKey(string $key): bool
    {
        return in_array($key, ['notice', 'success', 'warning', 'error', 'errors'], true)
            || str_ends_with($key, '.notice')
            || str_ends_with($key, '.success')
            || str_ends_with($key, '.warning')
            || str_ends_with($key, '.error')
            || str_ends_with($key, '.errors');
    }

    private static function isErrorKey(string $key): bool
    {
        return $key === 'errors' || str_ends_with($key, '.errors');
    }

    private static function normalizeMessage(mixed $value): ?string
    {
        if (is_string($value)) {
            $message = trim($value);

            return $message !== '' ? $message : null;
        }

        if (is_scalar($value)) {
            $message = trim((string) $value);

            return $message !== '' ? $message : null;
        }

        return null;
    }

    /**
     * @param mixed $value
     */
    private static function summaryMessage(mixed $value): ?string
    {
        if (is_string($value)) {
            $message = trim($value);

            return $message !== '' ? $message : null;
        }

        if (!is_array($value) || $value === []) {
            return null;
        }

        $messages = [];

        foreach ($value as $fieldMessages) {
            if (is_string($fieldMessages)) {
                $messages[] = trim($fieldMessages);
                continue;
            }

            if (!is_array($fieldMessages)) {
                continue;
            }

            foreach ($fieldMessages as $message) {
                if (!is_scalar($message)) {
                    continue;
                }

                $normalized = trim((string) $message);

                if ($normalized !== '') {
                    $messages[] = $normalized;
                }
            }
        }

        $messages = array_values(array_filter($messages, static fn (string $message): bool => $message !== ''));

        if ($messages === []) {
            return null;
        }

        $message = $messages[0];

        if (count($messages) > 1) {
            $message .= ' (' . (count($messages) - 1) . ' more)';
        }

        return $message;
    }

    private static function toneForMessage(string $key, string $message): string
    {
        if ($key === 'success' || str_ends_with($key, '.success')) {
            return 'success';
        }

        if ($key === 'warning' || str_ends_with($key, '.warning')) {
            return 'warning';
        }

        if ($key === 'error' || str_ends_with($key, '.error')) {
            return 'error';
        }

        $normalized = strtolower($message);

        if (preg_match('/\b(success|saved|created|updated|deleted|restored|sent|cleared|queued|imported|exported)\b/', $normalized) === 1) {
            return 'success';
        }

        if (preg_match('/\b(cannot|unable|failed|invalid|choose|confirm|not found|not available|denied|forbidden|unauthorized)\b/', $normalized) === 1) {
            return 'warning';
        }

        return 'info';
    }

    private static function titleForTone(string $tone): string
    {
        return match ($tone) {
            'success' => 'Success',
            'warning' => 'Warning',
            'error' => 'Error',
            default => 'Notice',
        };
    }

    private static function iconForTone(string $tone): string
    {
        return match ($tone) {
            'success' => 'badge-check',
            'warning' => 'circle-alert',
            'error' => 'circle-x',
            default => 'badge-info',
        };
    }
}
