<?php

declare(strict_types=1);

namespace App\Contracts;

interface DashboardWidgetProvider
{
    /**
     * @return array<string, mixed>|null
     */
    public function card(string $id, ?int $userId): ?array;
}
