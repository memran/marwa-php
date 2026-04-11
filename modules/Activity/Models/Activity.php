<?php

declare(strict_types=1);

namespace App\Modules\Activity\Models;

use Marwa\Framework\Database\Model;

final class Activity extends Model
{
    protected static ?string $table = 'activities';

    /**
     * @var list<string>
     */
    protected static array $fillable = [
        'action',
        'description',
        'actor_name',
        'actor_email',
        'subject_type',
        'subject_id',
        'details',
    ];

    /**
     * @var array<string, string>
     */
    protected static array $casts = [
        'subject_id' => 'int',
    ];

    public function readableDetails(): string
    {
        $raw = trim((string) $this->getAttribute('details'));

        if ($raw === '') {
            return '—';
        }

        $decoded = json_decode($raw, true);

        if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
            return $raw;
        }

        $summary = $this->stringValue($decoded['summary'] ?? null);
        $parts = [];

        if (isset($decoded['changes']) && is_array($decoded['changes'])) {
            foreach ($decoded['changes'] as $label => $change) {
                if (!is_array($change)) {
                    continue;
                }

                $parts[] = $this->formatChange((string) $label, $change['before'] ?? null, $change['after'] ?? null);
            }
        } elseif (isset($decoded['before'], $decoded['after']) && is_array($decoded['before']) && is_array($decoded['after'])) {
            foreach ($decoded['after'] as $label => $afterValue) {
                $parts[] = $this->formatChange((string) $label, $decoded['before'][$label] ?? null, $afterValue);
            }
        } elseif (isset($decoded['state']) && is_array($decoded['state'])) {
            foreach ($decoded['state'] as $label => $value) {
                $parts[] = $this->formatLabelValue((string) $label, $value);
            }
        } elseif (isset($decoded['items']) && is_array($decoded['items'])) {
            foreach ($decoded['items'] as $item) {
                if (!is_array($item) || !array_key_exists('label', $item)) {
                    continue;
                }

                $parts[] = $this->formatChange(
                    (string) $item['label'],
                    $item['before'] ?? null,
                    $item['after'] ?? null
                );
            }
        }

        if ($summary !== null && $parts !== []) {
            return $summary . ' ' . implode(' | ', $parts);
        }

        if ($summary !== null) {
            return $summary;
        }

        if ($parts !== []) {
            return implode(' | ', $parts);
        }

        return $raw;
    }

    private function formatChange(string $label, mixed $before, mixed $after): string
    {
        $label = ucfirst(str_replace('_', ' ', trim($label)));
        $beforeText = $this->stringValue($before);
        $afterText = $this->stringValue($after);

        if ($beforeText !== null && $afterText !== null) {
            return sprintf('%s: from %s to %s', $label, $beforeText, $afterText);
        }

        if ($afterText !== null) {
            return sprintf('%s: %s', $label, $afterText);
        }

        if ($beforeText !== null) {
            return sprintf('%s: %s', $label, $beforeText);
        }

        return $label;
    }

    private function formatLabelValue(string $label, mixed $value): string
    {
        $label = ucfirst(str_replace('_', ' ', trim($label)));
        $value = $this->stringValue($value);

        return $value !== null ? sprintf('%s: %s', $label, $value) : $label;
    }

    private function stringValue(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value !== '' ? $value : null;
    }
}
