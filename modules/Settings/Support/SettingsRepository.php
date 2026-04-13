<?php

declare(strict_types=1);

namespace App\Modules\Settings\Support;

use Marwa\DB\Connection\ConnectionManager;

final class SettingsRepository
{
    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        if (!app()->has(ConnectionManager::class)) {
            return [];
        }

        try {
            $statement = app(ConnectionManager::class)->getPdo()->query(
                'SELECT category, setting_key, setting_value FROM settings ORDER BY category ASC, setting_key ASC'
            );

            if ($statement === false) {
                return [];
            }

            $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);
            $values = [];

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $category = isset($row['category']) ? trim((string) $row['category']) : '';
                $key = isset($row['setting_key']) ? trim((string) $row['setting_key']) : '';

                if ($category === '' || $key === '') {
                    continue;
                }

                $values[$category . '.' . $key] = (string) ($row['setting_value'] ?? '');
            }

            return $values;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param list<array{category:string,key:string,value:string}> $rows
     */
    public function save(array $rows): void
    {
        if (!app()->has(ConnectionManager::class)) {
            throw new \RuntimeException('Database connection is required to persist settings.');
        }

        $pdo = app(ConnectionManager::class)->getPdo();
        $timestamp = gmdate('Y-m-d H:i:s');
        $update = $pdo->prepare(
            'UPDATE settings SET setting_value = :value, updated_at = :updated_at WHERE category = :category AND setting_key = :setting_key'
        );
        $insert = $pdo->prepare(
            'INSERT INTO settings (category, setting_key, setting_value, created_at, updated_at) VALUES (:category, :setting_key, :value, :created_at, :updated_at)'
        );

        $pdo->beginTransaction();

        try {
            foreach ($rows as $row) {
                $update->execute([
                    ':category' => $row['category'],
                    ':setting_key' => $row['key'],
                    ':value' => $row['value'],
                    ':updated_at' => $timestamp,
                ]);

                if ($update->rowCount() > 0) {
                    continue;
                }

                $insert->execute([
                    ':category' => $row['category'],
                    ':setting_key' => $row['key'],
                    ':value' => $row['value'],
                    ':created_at' => $timestamp,
                    ':updated_at' => $timestamp,
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }
}
