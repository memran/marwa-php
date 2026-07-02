<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Support;

use Marwa\DB\Facades\DB;

final class DashboardWidgetRepository
{
    private const TABLE = 'dashboard_widgets';

    /**
     * @return list<array<string, mixed>>
     */
    public function forUser(?int $userId): array
    {
        $widgets = $userId !== null
            ? $this->rowsForUser($userId)
            : $this->defaults();

        return $widgets !== [] ? $widgets : $this->defaults();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function defaults(): array
    {
        return $this->normalizeRows(
            DB::table(self::TABLE)
                ->whereNull('user_id')
                ->orderBy('position', 'asc')
                ->get()
        );
    }

    /**
     * @param list<array<string, mixed>> $widgets
     */
    public function saveForUser(?int $userId, array $widgets, callable $resolveWidget): void
    {
        if ($userId !== null) {
            $this->resetForUser($userId);
        }

        $now = date('Y-m-d H:i:s');

        foreach ($widgets as $index => $widget) {
            $widgetDefinition = $resolveWidget((string) ($widget['widget_id'] ?? ''));

            DB::table(self::TABLE)->insert([
                'user_id' => $userId,
                'widget_id' => (string) ($widget['widget_id'] ?? ''),
                'widget_type' => (string) ($widget['widget_type'] ?? 'system'),
                'title' => (string) ($widget['title'] ?? ($widgetDefinition['name'] ?? '')),
                'position' => $index,
                'width' => (string) ($widget['width'] ?? 'medium'),
                'enabled' => ($widget['enabled'] ?? false) ? 1 : 0,
                'config' => json_encode($widget['config'] ?? [], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function resetForUser(int $userId): void
    {
        DB::table(self::TABLE)->where('user_id', '=', $userId)->delete();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rowsForUser(int $userId): array
    {
        return $this->normalizeRows(
            DB::table(self::TABLE)
                ->where('user_id', '=', $userId)
                ->orderBy('position', 'asc')
                ->get()
        );
    }

    /**
     * @param array<int, mixed> $rows
     * @return list<array<string, mixed>>
     */
    private function normalizeRows(array $rows): array
    {
        return array_values(array_filter(
            array_map(static fn (mixed $row): ?array => is_array($row) ? $row : null, $rows),
            static fn (?array $row): bool => $row !== null
        ));
    }
}
