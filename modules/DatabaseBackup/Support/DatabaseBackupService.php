<?php

declare(strict_types=1);

namespace App\Modules\DatabaseBackup\Support;

use Marwa\DB\Connection\ConnectionManager;
use Marwa\DB\Facades\DB;
use Marwa\Framework\Application;
use Marwa\Framework\Supports\Storage;
use Psr\Http\Message\UploadedFileInterface;

final class DatabaseBackupService
{
    public function __construct(
        private readonly Application $app,
        private readonly BackupSettingsRepository $settings,
    ) {}

    /**
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
     * @return array{path: string, filename: string, message: string, tables: list<string>}
     */
    public function createBackup(?array $settings = null): array
    {
        $resolved = $settings ?? $this->settings->all();
        $tables = $this->tablesForSettings($resolved);

        if ($tables === []) {
            throw new \RuntimeException('No database tables were selected for backup.');
        }

        $snapshot = $this->snapshot($tables);
        $filename = $this->backupFilename($resolved, $tables);
        $relativePath = trim($this->storagePrefix($resolved) . '/' . $filename, '/');

        $this->storeArchive($resolved, $relativePath, $snapshot);

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

        return is_array($disks) && $disks !== [] ? array_values(array_keys($disks)) : ['local'];
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

        return array_values(array_filter(array_map('strval', is_array($tables) ? $tables : [])));
    }

    /**
     * @return list<string>
     */
    private function listMySqlTables(\PDO $pdo): array
    {
        $statement = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE' ORDER BY table_name");
        $tables = $statement instanceof \PDOStatement ? $statement->fetchAll(\PDO::FETCH_COLUMN) : [];

        return array_values(array_filter(array_map('strval', is_array($tables) ? $tables : [])));
    }

    /**
     * @param list<string> $tables
     * @return array<string, mixed>
     */
    private function snapshot(array $tables): array
    {
        $driver = $this->driver();

        $tableSnapshots = [];
        foreach ($tables as $table) {
            $tableSnapshots[] = [
                'name' => $table,
                'create_sql' => $this->createSql($table, $driver),
                'rows' => $this->tableRows($table),
            ];
        }

        return [
            'meta' => [
                'created_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
                'driver' => $driver,
                'database' => $this->databaseName(),
                'app_name' => (string) config('app.name', 'MarwaPHP'),
            ],
            'tables' => $tableSnapshots,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function tableRows(string $table): array
    {
        $rows = DB::table($table)->get();

        return array_map(static fn (array $row): array => $row, $rows);
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
     * @param array<string, mixed> $snapshot
     */
    private function storeArchive(array $settings, string $relativePath, array $snapshot): void
    {
        $storage = $this->storage($settings['storage_disk'] ?? null);
        $this->ensureDirectory($storage, $this->storagePrefix($settings));

        $archivePath = $this->tempArchivePath((string) ($settings['archive_format'] ?? 'zip'));
        $json = json_encode($snapshot, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        try {
            if (($settings['archive_format'] ?? 'zip') === 'tar') {
                $this->writeTarArchive($archivePath, 'backup.json', $json);
            } else {
                $this->writeZipArchive($archivePath, 'backup.json', $json);
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

    private function writeZipArchive(string $archivePath, string $entryName, string $contents): void
    {
        $zip = new \ZipArchive();
        $result = $zip->open($archivePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        if ($result !== true) {
            throw new \RuntimeException(sprintf('Unable to create ZIP archive [%s].', $archivePath));
        }

        $zip->addFromString($entryName, $contents);
        $zip->close();
    }

    private function writeTarArchive(string $archivePath, string $entryName, string $contents): void
    {
        $header = $this->tarHeader($entryName, strlen($contents));
        $padding = str_repeat("\0", (512 - (strlen($contents) % 512)) % 512);
        $data = $header . $contents . $padding . str_repeat("\0", 1024);

        if (file_put_contents($archivePath, $data, LOCK_EX) === false) {
            throw new \RuntimeException(sprintf('Unable to create TAR archive [%s].', $archivePath));
        }
    }

    private function tarHeader(string $name, int $size): string
    {
        $header = str_pad($name, 100, "\0")
            . str_pad('0000777', 8, "\0", STR_PAD_LEFT)
            . str_pad('0000000', 8, "\0", STR_PAD_LEFT)
            . str_pad('0000000', 8, "\0", STR_PAD_LEFT)
            . str_pad(sprintf('%011o', $size), 12, "\0", STR_PAD_LEFT)
            . str_pad(sprintf('%011o', time()), 12, "\0", STR_PAD_LEFT)
            . str_repeat(' ', 8)
            . '0'
            . str_repeat("\0", 100)
            . "ustar\0"
            . "00"
            . str_pad('', 32, "\0")
            . str_pad('', 32, "\0")
            . str_pad('', 8, "\0")
            . str_pad('', 8, "\0")
            . str_pad('', 155, "\0")
            . str_pad('', 12, "\0");

        $checksum = array_sum(array_map('ord', str_split($header)));
        $checksumField = str_pad(sprintf('%06o', $checksum), 6, '0', STR_PAD_LEFT) . "\0 ";

        return substr_replace($header, $checksumField, 148, 8);
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
        $tables = $this->restoreSnapshot($snapshot);

        return [
            'path' => $archivePath,
            'filename' => $sourceName,
            'message' => sprintf('Database restored from %s. All existing data was replaced.', $sourceName),
            'tables' => $tables,
        ];
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

        if (!is_array($decoded) || !isset($decoded['tables']) || !is_array($decoded['tables'])) {
            throw new \RuntimeException('The backup archive is missing a valid snapshot payload.');
        }

        return $decoded;
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

        $json = $zip->getFromName('backup.json');
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

                if ($name === 'backup.json') {
                    return $content;
                }
            }
        } finally {
            fclose($handle);
        }

        throw new \RuntimeException('TAR backup archive did not contain backup.json.');
    }

    private function backupFilename(array $settings, array $tables): string
    {
        $scope = ($settings['scope'] ?? 'full') === 'selected'
            ? 'tables-' . $this->tableSegment($tables)
            : 'full';
        $name = sanitize_filename(sprintf(
            '%s-db-backup-%s-%s',
            (string) config('app.name', 'marwa'),
            $scope,
            (new \DateTimeImmutable())->format('Ymd-His')
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

        return $time->format('i') !== '' && ((int) $time->format('i') % $minutes === 0);
    }
}
