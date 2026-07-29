<?php

declare(strict_types=1);

namespace App\Modules\DatabaseBackup\Support;

use Marwa\DB\Connection\ConnectionManager;
use Marwa\DB\Facades\DB;
use Marwa\Framework\Supports\Storage;
use Psr\Http\Message\UploadedFileInterface;

final class DatabaseBackupService
{
    private const SNAPSHOT_ENTRY = 'backup.json';

    public function __construct(
        private readonly BackupSettingsRepository $settings,
    ) {}

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $current
     * @return array{values: array<string, mixed>, errors: list<string>}
     */
    public function normalizeSettingsSubmission(array $input, array $current = []): array
    {
        $values = array_replace_recursive($this->settings->defaults(), $current);
        $errors = [];

        $values['enabled'] = $this->boolean($input['enabled'] ?? $values['enabled']);

        $mode = $this->stringValue($input['mode'] ?? $values['mode']);
        if (!array_key_exists($mode, $this->scheduleModes())) {
            $errors[] = 'Choose a valid backup frequency.';
        } else {
            $values['mode'] = $mode;
        }

        $time = $this->timeValue($input['time'] ?? $values['time']);
        if ($time === null) {
            $errors[] = 'Use HH:MM for the backup time.';
        } else {
            $values['time'] = $time;
        }

        $values['day_of_week'] = $this->clampInt($input['day_of_week'] ?? $values['day_of_week'], 1, 7);
        $values['day_of_month'] = $this->clampInt($input['day_of_month'] ?? $values['day_of_month'], 1, 31);

        $interval = $this->positiveInt($input['interval_minutes'] ?? $values['interval_minutes']);
        if ($interval === null) {
            $errors[] = 'Interval minutes must be 1 or higher.';
        } else {
            $values['interval_minutes'] = $interval;
        }

        $disk = $this->stringValue($input['storage_disk'] ?? $values['storage_disk']);
        if ($disk === '' || !in_array($disk, $this->availableStorageDisks(), true)) {
            $errors[] = 'Choose a configured storage disk.';
        } else {
            $values['storage_disk'] = $disk;
        }

        $values['storage_path'] = $this->storagePath($this->stringValue($input['storage_path'] ?? $values['storage_path']));

        $format = strtolower($this->stringValue($input['archive_format'] ?? $values['archive_format']));
        if (!in_array($format, $this->archiveFormats(), true)) {
            $errors[] = 'Choose ZIP or TAR for the backup archive.';
        } else {
            $values['archive_format'] = $format;
        }

        $scope = $this->stringValue($input['scope'] ?? $values['scope']);
        if (!in_array($scope, ['full', 'selected'], true)) {
            $errors[] = 'Choose a backup scope.';
        } else {
            $values['scope'] = $scope;
        }

        $tables = $this->parseTableList($input['tables'] ?? $values['tables']);
        if ($values['scope'] === 'selected' && $tables === []) {
            $errors[] = 'Add at least one table when using the selected tables scope.';
        }
        $values['tables'] = $tables;

        return [
            'values' => $values,
            'errors' => $errors,
        ];
    }

    public function isScheduleDue(?\DateTimeImmutable $time = null): bool
    {
        $settings = $this->settings->all();

        if (!($settings['enabled'] ?? false)) {
            return false;
        }

        $time ??= new \DateTimeImmutable();

        return match ($settings['mode'] ?? 'daily_at') {
            'every_minutes' => $this->isEveryMinutesDue($time, (int) ($settings['interval_minutes'] ?? 1)),
            'hourly' => $time->format('i') === '00',
            'daily_at' => $time->format('H:i') === (string) ($settings['time'] ?? '02:00'),
            'weekly_at' => $time->format('N') === (string) (int) ($settings['day_of_week'] ?? 1)
                && $time->format('H:i') === (string) ($settings['time'] ?? '02:00'),
            'monthly_at' => $time->format('j') === (string) (int) ($settings['day_of_month'] ?? 1)
                && $time->format('H:i') === (string) ($settings['time'] ?? '02:00'),
            default => false,
        };
    }

    /**
     * @param array<string, mixed>|null $settings
     * @return array{path: string, filename: string, message: string, tables: list<string>}
     */
    public function createBackup(?array $settings = null): array
    {
        $resolved = $settings ?? $this->settings->all();
        $tables = $this->tablesForSettings($resolved);

        if ($tables === []) {
            throw new \RuntimeException('No database tables were selected for backup.');
        }

        $filename = $this->backupFilename($resolved, $tables);
        $relativePath = trim($this->storagePrefix($resolved) . '/' . $filename, '/');
        $snapshotPath = $this->writeSnapshotFile($tables);

        try {
            $this->storeArchive($resolved, $relativePath, $snapshotPath);
        } finally {
            $this->deleteIfExists($snapshotPath);
        }

        return [
            'path' => $relativePath,
            'filename' => $filename,
            'message' => sprintf('Database backup created: %s', $filename),
            'tables' => $tables,
        ];
    }

    /**
     * @return array{path: string, filename: string, message: string, tables: list<string>}
     */
    public function runScheduledBackup(?\DateTimeImmutable $time = null): array
    {
        if (!$this->isScheduleDue($time)) {
            return [
                'path' => '',
                'filename' => '',
                'message' => 'Database backup schedule is not due.',
                'tables' => [],
            ];
        }

        return $this->createBackup();
    }

    /**
     * @return array{path: string, filename: string, message: string, tables: list<string>}
     */
    public function restoreFromStoredBackup(string $relativePath): array
    {
        $storage = $this->storage();

        if (!in_array($relativePath, array_column($this->availableBackups(), 'path'), true)) {
            throw new \RuntimeException('Choose a backup from the configured storage location.');
        }

        return $this->restoreFromArchiveFile($storage->path($relativePath), basename($relativePath));
    }

    /**
     * @return array{path: string, filename: string, message: string, tables: list<string>}
     */
    public function restoreFromUploadedFile(UploadedFileInterface $file): array
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('The uploaded backup archive could not be read.');
        }

        $clientName = (string) $file->getClientFilename();
        $extension = strtolower(pathinfo($clientName, PATHINFO_EXTENSION));

        if (!in_array($extension, $this->archiveFormats(), true)) {
            throw new \RuntimeException('Upload a ZIP or TAR backup archive.');
        }

        $tempPath = $this->tempArchivePath($extension);
        $file->moveTo($tempPath);

        try {
            return $this->restoreFromArchiveFile($tempPath, basename($clientName) !== '' ? basename($clientName) : basename($tempPath));
        } finally {
            $this->deleteIfExists($tempPath);
        }
    }

    /**
     * @return list<string>
     */
    public function availableStorageDisks(): array
    {
        $disks = config('storage.disks', []);

        return is_array($disks) && $disks !== [] ? array_keys($disks) : ['local'];
    }

    /**
     * @return array<string, string>
     */
    public function scheduleModes(): array
    {
        return [
            'every_minutes' => 'Every N minutes',
            'hourly' => 'Hourly',
            'daily_at' => 'Daily at a time',
            'weekly_at' => 'Weekly at a time',
            'monthly_at' => 'Monthly at a time',
        ];
    }

    /**
     * @return list<string>
     */
    public function archiveFormats(): array
    {
        return ['zip', 'tar'];
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function scheduleLabel(array $settings): string
    {
        if (!($settings['enabled'] ?? false)) {
            return 'Disabled';
        }

        return match ($settings['mode'] ?? 'daily_at') {
            'every_minutes' => sprintf('Every %d minute(s)', max(1, (int) ($settings['interval_minutes'] ?? 1))),
            'hourly' => 'Hourly',
            'daily_at' => sprintf('Daily at %s', (string) ($settings['time'] ?? '02:00')),
            'weekly_at' => sprintf(
                'Weekly on day %d at %s',
                max(1, min(7, (int) ($settings['day_of_week'] ?? 1))),
                (string) ($settings['time'] ?? '02:00')
            ),
            'monthly_at' => sprintf(
                'Monthly on day %d at %s',
                max(1, min(31, (int) ($settings['day_of_month'] ?? 1))),
                (string) ($settings['time'] ?? '02:00')
            ),
            default => 'Disabled',
        };
    }

    /**
     * @return list<array{path: string, filename: string, size: int, modified_at: int, format: string}>
     */
    public function availableBackups(): array
    {
        $settings = $this->settings->all();
        $storage = $this->storage($settings['storage_disk'] ?? null);
        $directory = $this->storagePrefix($settings);

        try {
            $files = $storage->files($directory);
        } catch (\Throwable) {
            return [];
        }

        $backups = [];

        foreach ($files as $file) {
            $format = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($format, $this->archiveFormats(), true)) {
                continue;
            }

            $backups[] = [
                'path' => $file,
                'filename' => basename($file),
                'size' => $storage->size($file),
                'modified_at' => $storage->lastModified($file),
                'format' => $format,
            ];
        }

        usort($backups, static fn (array $left, array $right): int => $right['modified_at'] <=> $left['modified_at']);

        return $backups;
    }

    /**
     * @param array<string, mixed> $settings
     * @return list<string>
     */
    private function tablesForSettings(array $settings): array
    {
        $allTables = $this->listTables();

        if (($settings['scope'] ?? 'full') === 'selected') {
            $selected = array_values(array_intersect($this->parseTableList($settings['tables'] ?? []), $allTables));

            return $selected;
        }

        return $allTables;
    }

    /**
     * @return list<string>
     */
    private function listTables(): array
    {
        $driver = $this->driver();
        $pdo = $this->pdo();

        return match ($driver) {
            'sqlite' => $this->listSqliteTables($pdo),
            'mysql', 'mariadb' => $this->listMySqlTables($pdo),
            default => throw new \RuntimeException(sprintf('Database driver [%s] is not supported for backups.', $driver)),
        };
    }

    /**
     * @return list<string>
     */
    private function listSqliteTables(\PDO $pdo): array
    {
        $statement = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
        $tables = $statement instanceof \PDOStatement ? $statement->fetchAll(\PDO::FETCH_COLUMN) : [];

        return array_values(array_filter(array_map('strval', $tables)));
    }

    /**
     * @return list<string>
     */
    private function listMySqlTables(\PDO $pdo): array
    {
        $statement = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE' ORDER BY table_name");
        $tables = $statement instanceof \PDOStatement ? $statement->fetchAll(\PDO::FETCH_COLUMN) : [];

        return array_values(array_filter(array_map('strval', $tables)));
    }

    /**
     * @param list<string> $tables
     */
    private function writeSnapshotFile(array $tables): string
    {
        $path = $this->tempArchivePath('json');
        $handle = fopen($path, 'wb');

        if (!is_resource($handle)) {
            throw new \RuntimeException('Unable to create the temporary database snapshot.');
        }

        $driver = $this->driver();
        $meta = [
            'created_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'driver' => $driver,
            'database' => $this->databaseName(),
            'app_name' => (string) config('app.name', 'MarwaPHP'),
        ];

        try {
            $this->writeStream($handle, '{"meta":');
            $this->writeStream($handle, $this->encodeJson($meta));
            $this->writeStream($handle, ',"tables":[');

            foreach ($tables as $index => $table) {
                if ($index > 0) {
                    $this->writeStream($handle, ',');
                }

                $this->writeStream($handle, '{"name":');
                $this->writeStream($handle, $this->encodeJson($table));
                $this->writeStream($handle, ',"create_sql":');
                $this->writeStream($handle, $this->encodeJson($this->createSql($table, $driver)));
                $this->writeStream($handle, ',"rows":[');
                $this->writeTableRows($handle, $table, $driver);
                $this->writeStream($handle, ']}');
            }

            $this->writeStream($handle, ']}');
        } catch (\Throwable $exception) {
            fclose($handle);
            $this->deleteIfExists($path);

            throw $exception;
        }

        fclose($handle);

        return $path;
    }

    /**
     * @param resource $handle
     */
    private function writeTableRows($handle, string $table, string $driver): void
    {
        $pdo = $this->pdo();
        $bufferAttribute = null;
        $previousBuffering = null;

        if (
            in_array($driver, ['mysql', 'mariadb'], true)
            && defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')
        ) {
            $attribute = constant('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY');
            $bufferAttribute = $attribute;

            try {
                $previousBuffering = $pdo->getAttribute($attribute);
                $pdo->setAttribute($attribute, false);
            } catch (\Throwable) {
                $bufferAttribute = null;
                $previousBuffering = null;
            }
        }

        $statement = $pdo->query(sprintf('SELECT * FROM %s', $this->quotedIdentifier($table, $driver)));

        if (!$statement instanceof \PDOStatement) {
            throw new \RuntimeException(sprintf('Unable to read rows from table [%s].', $table));
        }

        try {
            $first = true;
            while (($row = $statement->fetch(\PDO::FETCH_ASSOC)) !== false) {
                if (!$first) {
                    $this->writeStream($handle, ',');
                }

                $this->writeStream($handle, $this->encodeJson($row));
                $first = false;
            }
        } finally {
            $statement->closeCursor();

            if ($bufferAttribute !== null && $previousBuffering !== null) {
                $pdo->setAttribute($bufferAttribute, $previousBuffering);
            }
        }
    }

    /**
     * @param resource $handle
     */
    private function writeStream($handle, string $contents): void
    {
        $length = strlen($contents);
        $written = 0;

        while ($written < $length) {
            $bytes = fwrite($handle, substr($contents, $written));
            if ($bytes === false || $bytes === 0) {
                throw new \RuntimeException('Unable to write the database snapshot.');
            }

            $written += $bytes;
        }
    }

    private function encodeJson(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    private function createSql(string $table, string $driver): string
    {
        $pdo = $this->pdo();

        return match ($driver) {
            'sqlite' => $this->sqliteCreateSql($pdo, $table),
            'mysql', 'mariadb' => $this->mysqlCreateSql($pdo, $table),
            default => throw new \RuntimeException(sprintf('Database driver [%s] is not supported for backups.', $driver)),
        };
    }

    private function sqliteCreateSql(\PDO $pdo, string $table): string
    {
        $statement = $pdo->prepare("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = :table");
        $statement->execute(['table' => $table]);
        $sql = $statement->fetchColumn();

        if (!is_string($sql) || $sql === '') {
            throw new \RuntimeException(sprintf('Unable to read schema for table [%s].', $table));
        }

        return $sql;
    }

    private function mysqlCreateSql(\PDO $pdo, string $table): string
    {
        $statement = $pdo->query(sprintf('SHOW CREATE TABLE `%s`', str_replace('`', '``', $table)));
        $row = $statement instanceof \PDOStatement ? $statement->fetch(\PDO::FETCH_ASSOC) : false;

        if (!is_array($row)) {
            throw new \RuntimeException(sprintf('Unable to read schema for table [%s].', $table));
        }

        $sql = $row['Create Table'] ?? $row['Create View'] ?? null;

        if (!is_string($sql) || $sql === '') {
            throw new \RuntimeException(sprintf('Unable to read schema for table [%s].', $table));
        }

        return $sql;
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function storeArchive(array $settings, string $relativePath, string $snapshotPath): void
    {
        $storage = $this->storage($settings['storage_disk'] ?? null);
        $this->ensureDirectory($storage, $this->storagePrefix($settings));

        $archivePath = $this->tempArchivePath((string) ($settings['archive_format'] ?? 'zip'));

        try {
            if (($settings['archive_format'] ?? 'zip') === 'tar') {
                $this->writeTarArchive($archivePath, self::SNAPSHOT_ENTRY, $snapshotPath);
            } else {
                $this->writeZipArchive($archivePath, self::SNAPSHOT_ENTRY, $snapshotPath);
            }

            $stream = fopen($archivePath, 'rb');
            if (!is_resource($stream)) {
                throw new \RuntimeException('Unable to open the generated archive for storage.');
            }

            try {
                $storage->writeStream($relativePath, $stream);
            } finally {
                fclose($stream);
            }
        } finally {
            $this->deleteIfExists($archivePath);
        }
    }

    private function writeZipArchive(string $archivePath, string $entryName, string $snapshotPath): void
    {
        $zip = new \ZipArchive();
        $result = $zip->open($archivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        if ($result !== true) {
            throw new \RuntimeException(sprintf('Unable to create ZIP archive [%s].', $archivePath));
        }

        if (!$zip->addFile($snapshotPath, $entryName)) {
            $zip->close();

            throw new \RuntimeException('Unable to add the database snapshot to the ZIP archive.');
        }

        if (!$zip->close()) {
            throw new \RuntimeException('Unable to finish the ZIP backup archive.');
        }
    }

    private function writeTarArchive(string $archivePath, string $entryName, string $snapshotPath): void
    {
        try {
            $archive = new \PharData($archivePath);
            $archive->addFile($snapshotPath, $entryName);
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Unable to create the TAR backup archive.', 0, $exception);
        }
    }

    /**
     * @return array{path: string, filename: string, message: string, tables: list<string>}
     */
    private function restoreFromArchiveFile(string $archivePath, string $sourceName): array
    {
        if (!is_file($archivePath)) {
            throw new \RuntimeException(sprintf('Backup archive [%s] does not exist.', $archivePath));
        }

        $snapshot = $this->readSnapshot($archivePath);
        $safetyBackup = $this->createRestoreSafetyBackup();

        try {
            $tables = $this->restoreSnapshot($snapshot);
        } catch (\Throwable $restoreException) {
            if ($safetyBackup === null) {
                throw $restoreException;
            }

            try {
                $safetyDisk = (string) ($this->settings->all()['storage_disk'] ?? '');
                $safetyPath = $this->storage($safetyDisk)->path($safetyBackup['path']);
                $this->restoreSnapshot($this->readSnapshot($safetyPath));
            } catch (\Throwable $recoveryException) {
                throw new \RuntimeException(sprintf(
                    'Restore failed and automatic recovery also failed. Use safety backup [%s]. Recovery error: %s',
                    $safetyBackup['path'],
                    $recoveryException->getMessage()
                ), 0, $restoreException);
            }

            throw new \RuntimeException(sprintf(
                'Restore failed. The previous database was recovered automatically from safety backup [%s].',
                $safetyBackup['path']
            ), 0, $restoreException);
        }

        $safetyMessage = $safetyBackup !== null
            ? sprintf(' Pre-restore safety backup: %s.', $safetyBackup['path'])
            : '';

        return [
            'path' => $archivePath,
            'filename' => $sourceName,
            'message' => sprintf(
                'Database restored from %s. All existing data was replaced.%s',
                $sourceName,
                $safetyMessage
            ),
            'tables' => $tables,
        ];
    }

    /**
     * @return array{path: string, filename: string, message: string, tables: list<string>}|null
     */
    private function createRestoreSafetyBackup(): ?array
    {
        if (!in_array($this->driver(), ['mysql', 'mariadb'], true)) {
            return null;
        }

        $settings = array_replace_recursive($this->settings->all(), [
            'scope' => 'full',
            'tables' => [],
        ]);

        return $this->createBackup($settings);
    }

    /**
     * @param array<string, mixed> $snapshot
     * @return list<string>
     */
    private function restoreSnapshot(array $snapshot): array
    {
        $pdo = $this->pdo();
        $driver = $this->driver();
        $tables = [];
        $foreignKeysDisabled = false;

        try {
            if ($driver === 'sqlite' && !$pdo->inTransaction()) {
                $pdo->beginTransaction();
            }

            $foreignKeysDisabled = $this->disableForeignKeys($pdo, $driver);
            $this->dropAllTables($pdo, $driver);

            foreach ($snapshot['tables'] as $tableSnapshot) {
                if (!is_array($tableSnapshot)) {
                    continue;
                }

                $tableName = (string) ($tableSnapshot['name'] ?? '');
                $createSql = (string) ($tableSnapshot['create_sql'] ?? '');
                $rows = is_array($tableSnapshot['rows'] ?? null) ? $tableSnapshot['rows'] : [];

                if ($tableName === '' || $createSql === '') {
                    continue;
                }

                $tables[] = $tableName;
                $pdo->exec($createSql);
                $this->insertRows($tableName, $rows);
            }

            if ($driver === 'sqlite' && $pdo->inTransaction()) {
                $pdo->commit();
            }
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        } finally {
            if ($foreignKeysDisabled) {
                $this->enableForeignKeys($pdo, $driver);
            }
        }

        return $tables;
    }

    private function dropAllTables(\PDO $pdo, string $driver): void
    {
        $tables = $this->listTables();

        foreach (array_reverse($tables) as $table) {
            $pdo->exec(sprintf('DROP TABLE IF EXISTS %s', $this->quotedIdentifier($table, $driver)));
        }
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function insertRows(string $table, array $rows): void
    {
        foreach ($rows as $row) {
            DB::table($table)->insert($row);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readSnapshot(string $archivePath): array
    {
        $extension = strtolower(pathinfo($archivePath, PATHINFO_EXTENSION));

        $json = match ($extension) {
            'zip' => $this->readZipSnapshot($archivePath),
            'tar' => $this->readTarSnapshot($archivePath),
            default => throw new \RuntimeException(sprintf('Unsupported backup archive type [%s].', $extension)),
        };

        /** @var mixed $decoded */
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            throw new \RuntimeException('The backup archive is missing a valid snapshot payload.');
        }

        return $this->validateSnapshot($decoded);
    }

    /**
     * @param array<string, mixed> $snapshot
     * @return array<string, mixed>
     */
    private function validateSnapshot(array $snapshot): array
    {
        $meta = $snapshot['meta'] ?? null;
        $tables = $snapshot['tables'] ?? null;

        if (!is_array($meta) || !is_array($tables)) {
            throw new \RuntimeException('The backup archive is missing valid metadata or table data.');
        }

        $snapshotDriver = $this->normalizedDriver((string) ($meta['driver'] ?? ''));
        $currentDriver = $this->normalizedDriver($this->driver());

        if ($snapshotDriver === '' || $snapshotDriver !== $currentDriver) {
            throw new \RuntimeException(sprintf(
                'Backup driver [%s] is not compatible with the current database driver [%s].',
                (string) ($meta['driver'] ?? 'unknown'),
                $this->driver()
            ));
        }

        $seen = [];
        foreach ($tables as $table) {
            if (!is_array($table)) {
                throw new \RuntimeException('The backup archive contains an invalid table entry.');
            }

            $name = (string) ($table['name'] ?? '');
            $createSql = (string) ($table['create_sql'] ?? '');
            $rows = $table['rows'] ?? null;

            if (!preg_match('/^[A-Za-z0-9_]+$/', $name) || isset($seen[$name])) {
                throw new \RuntimeException(sprintf('The backup archive contains an invalid or duplicate table [%s].', $name));
            }

            if (!$this->isSafeCreateTableSql($createSql, $name)) {
                throw new \RuntimeException(sprintf('The backup schema for table [%s] is not safe to restore.', $name));
            }

            if (!is_array($rows)) {
                throw new \RuntimeException(sprintf('The backup rows for table [%s] are invalid.', $name));
            }

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    throw new \RuntimeException(sprintf('The backup contains an invalid row for table [%s].', $name));
                }
            }

            $seen[$name] = true;
        }

        return $snapshot;
    }

    private function isSafeCreateTableSql(string $sql, string $table): bool
    {
        if ($sql === '' || str_contains($sql, "\0")) {
            return false;
        }

        $quotedTable = preg_quote($table, '/');
        $identifier = sprintf('(?:`%1$s`|"%1$s"|\\[%1$s\\]|%1$s)', $quotedTable);

        if (!preg_match(
            '/^\s*CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?' . $identifier . '\s*\(/i',
            $sql
        )) {
            return false;
        }

        return !preg_match(
            '/;\s*(?:ALTER|ATTACH|CREATE|DELETE|DETACH|DROP|INSERT|PRAGMA|REPLACE|SET|TRUNCATE|UPDATE)\b/i',
            $sql
        );
    }

    private function normalizedDriver(string $driver): string
    {
        $driver = strtolower(trim($driver));

        return in_array($driver, ['mysql', 'mariadb'], true) ? 'mysql' : $driver;
    }

    private function disableForeignKeys(\PDO $pdo, string $driver): bool
    {
        return match ($driver) {
            'sqlite' => $pdo->exec('PRAGMA foreign_keys = OFF') !== false,
            'mysql', 'mariadb' => $pdo->exec('SET FOREIGN_KEY_CHECKS = 0') !== false,
            default => false,
        };
    }

    private function enableForeignKeys(\PDO $pdo, string $driver): void
    {
        match ($driver) {
            'sqlite' => $pdo->exec('PRAGMA foreign_keys = ON'),
            'mysql', 'mariadb' => $pdo->exec('SET FOREIGN_KEY_CHECKS = 1'),
            default => null,
        };
    }

    private function readZipSnapshot(string $archivePath): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($archivePath) !== true) {
            throw new \RuntimeException(sprintf('Unable to open ZIP archive [%s].', $archivePath));
        }

        $json = $zip->getFromName(self::SNAPSHOT_ENTRY);
        $zip->close();

        if (!is_string($json) || $json === '') {
            throw new \RuntimeException('ZIP backup archive did not contain backup.json.');
        }

        return $json;
    }

    private function readTarSnapshot(string $archivePath): string
    {
        $handle = fopen($archivePath, 'rb');
        if (!is_resource($handle)) {
            throw new \RuntimeException(sprintf('Unable to open TAR archive [%s].', $archivePath));
        }

        try {
            while (!feof($handle)) {
                $header = fread($handle, 512);
                if (!is_string($header) || strlen($header) < 512) {
                    break;
                }

                if (trim($header, "\0") === '') {
                    break;
                }

                $name = rtrim(substr($header, 0, 100), "\0");
                $size = octdec(trim(substr($header, 124, 12), "\0 "));

                $content = '';
                if ($size > 0) {
                    $content = fread($handle, $size);
                    if (!is_string($content) || strlen($content) < $size) {
                        throw new \RuntimeException('Unable to read TAR backup payload.');
                    }
                }

                $padding = (512 - ($size % 512)) % 512;
                if ($padding > 0) {
                    fread($handle, $padding);
                }

                if ($name === self::SNAPSHOT_ENTRY) {
                    return $content;
                }
            }
        } finally {
            fclose($handle);
        }

        throw new \RuntimeException('TAR backup archive did not contain backup.json.');
    }

    /**
     * @param array<string, mixed> $settings
     * @param list<string> $tables
     */
    private function backupFilename(array $settings, array $tables): string
    {
        $scope = ($settings['scope'] ?? 'full') === 'selected'
            ? 'tables-' . $this->tableSegment($tables)
            : 'full';
        $name = sanitize_filename(sprintf(
            '%s-db-backup-%s-%s',
            (string) config('app.name', 'marwa'),
            $scope,
            (new \DateTimeImmutable())->format('Ymd-His-u') . '-' . bin2hex(random_bytes(3))
        ));

        return $name . '.' . ($settings['archive_format'] ?? 'zip');
    }

    /**
     * @param list<string> $tables
     */
    private function tableSegment(array $tables): string
    {
        $segment = implode('-', array_slice($tables, 0, 4));
        if (count($tables) > 4) {
            $segment .= '-more-' . count($tables);
        }

        return sanitize_filename($segment !== '' ? $segment : 'selected');
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function storagePrefix(array $settings): string
    {
        return $this->storagePath($this->stringValue($settings['storage_path'] ?? 'database-backups'));
    }

    private function storage(?string $disk = null): Storage
    {
        return storage($disk);
    }

    private function ensureDirectory(Storage $storage, string $directory): void
    {
        if ($directory === '') {
            return;
        }

        $storage->makeDirectory($directory);
    }

    private function tempArchivePath(string $extension): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'marwa-db-backup-' . bin2hex(random_bytes(8)) . '.' . $extension;
    }

    private function deleteIfExists(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function driver(): string
    {
        return app(ConnectionManager::class)->getDriver();
    }

    private function databaseName(): string
    {
        $config = config('database.connections', []);
        $default = (string) config('database.default', 'default');

        if (is_array($config) && isset($config[$default]['database']) && is_string($config[$default]['database'])) {
            return $config[$default]['database'];
        }

        return $default;
    }

    private function pdo(): \PDO
    {
        return app(ConnectionManager::class)->getPdo();
    }

    private function quotedIdentifier(string $identifier, string $driver): string
    {
        return match ($driver) {
            'mysql', 'mariadb' => '`' . str_replace('`', '``', $identifier) . '`',
            default => '"' . str_replace('"', '""', $identifier) . '"',
        };
    }

    private function boolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function positiveInt(mixed $value): ?int
    {
        $int = (int) $value;

        return $int > 0 ? $int : null;
    }

    private function clampInt(mixed $value, int $min, int $max): int
    {
        return max($min, min($max, (int) $value));
    }

    private function stringValue(mixed $value): string
    {
        return trim((string) $value);
    }

    private function timeValue(mixed $value): ?string
    {
        $value = $this->stringValue($value);
        if (!preg_match('/^\d{2}:\d{2}$/', $value)) {
            return null;
        }

        $time = \DateTimeImmutable::createFromFormat('H:i', $value);

        return $time instanceof \DateTimeImmutable ? $time->format('H:i') : null;
    }

    private function storagePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $segments = [];

        foreach (explode('/', $path) as $segment) {
            $segment = trim($segment);

            if ($segment === '' || $segment === '.' || $segment === '..') {
                continue;
            }

            $segments[] = sanitize_filename($segment);
        }

        $normalized = trim(implode('/', array_filter($segments)), '/');

        return $normalized !== '' ? $normalized : 'database-backups';
    }

    /**
     * @return list<string>
     */
    private function parseTableList(mixed $tables): array
    {
        if (is_array($tables)) {
            $entries = $tables;
        } else {
            $raw = trim((string) $tables);
            if ($raw === '') {
                return [];
            }

            $entries = preg_split('/[\r\n,]+/', $raw) ?: [];
        }

        $normalized = [];
        foreach ($entries as $entry) {
            $name = trim((string) $entry);
            if ($name === '') {
                continue;
            }

            if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
                continue;
            }

            $normalized[] = $name;
        }

        return array_values(array_unique($normalized));
    }

    private function isEveryMinutesDue(\DateTimeImmutable $time, int $minutes): bool
    {
        $minutes = max(1, $minutes);
        $anchor = new \DateTimeImmutable('1970-01-01 00:00:00', $time->getTimezone());
        $elapsedMinutes = intdiv($time->getTimestamp() - $anchor->getTimestamp(), 60);

        return $elapsedMinutes >= 0 && $elapsedMinutes % $minutes === 0;
    }
}
