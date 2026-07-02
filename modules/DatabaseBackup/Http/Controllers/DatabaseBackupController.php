<?php

declare(strict_types=1);

namespace App\Modules\DatabaseBackup\Http\Controllers;

use App\Modules\DatabaseBackup\Support\BackupSettingsRepository;
use App\Modules\DatabaseBackup\Support\DatabaseBackupService;
use Marwa\Framework\Controllers\Controller;
use Marwa\Router\Http\Input;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

final class DatabaseBackupController extends Controller
{
    public function __construct(
        private readonly DatabaseBackupService $service,
        private readonly BackupSettingsRepository $settings,
    ) {}

    public function index(): ResponseInterface
    {
        $current = $this->settings->all();
        $draft = session('database_backup.form', []);

        if (is_array($draft)) {
            $current = array_replace_recursive($current, $draft);
        }

        return $this->view('@database_backup/index', [
            'settings' => $current,
            'schedule_label' => $this->service->scheduleLabel($current),
            'backups' => $this->service->availableBackups(),
            'storage_disks' => $this->service->availableStorageDisks(),
            'modes' => $this->service->scheduleModes(),
            'formats' => $this->service->archiveFormats(),
        ]);
    }

    public function updateSettings(ServerRequestInterface $request): ResponseInterface
    {
        Input::setRequest($request);

        $submitted = Input::post('backup_settings', []);
        $submitted = is_array($submitted) ? $submitted : [];

        $normalized = $this->service->normalizeSettingsSubmission($submitted, $this->settings->all());

        if ($normalized['errors'] !== []) {
            session()->flash('database_backup.errors', $normalized['errors']);
            session()->flash('database_backup.form', $submitted);

            return $this->redirect('/admin/database-backups');
        }

        $this->settings->save($normalized['values']);
        session()->flash('database_backup.notice', 'Backup settings saved.');

        return $this->redirect('/admin/database-backups');
    }

    public function backupNow(): ResponseInterface
    {
        try {
            $result = $this->service->createBackup();
            session()->flash('database_backup.notice', $result['message']);
        } catch (\Throwable $exception) {
            session()->flash('database_backup.errors', [$exception->getMessage()]);
        }

        return $this->redirect('/admin/database-backups');
    }

    public function restore(ServerRequestInterface $request): ResponseInterface
    {
        Input::setRequest($request);

        $confirmed = (bool) Input::post('confirm_restore', false);

        if (!$confirmed) {
            session()->flash('database_backup.errors', ['Confirm the restore warning before continuing.']);

            return $this->redirect('/admin/database-backups');
        }

        $source = trim((string) Input::post('selected_backup', ''));
        $upload = $this->uploadedArchive();

        try {
            if ($upload instanceof UploadedFileInterface) {
                $result = $this->service->restoreFromUploadedFile($upload);
            } elseif ($source !== '') {
                $result = $this->service->restoreFromStoredBackup($source);
            } else {
                throw new \RuntimeException('Choose a stored backup or upload a zip/tar archive to restore.');
            }

            session()->flash('database_backup.notice', $result['message']);
        } catch (\Throwable $exception) {
            session()->flash('database_backup.errors', [$exception->getMessage()]);
        }

        return $this->redirect('/admin/database-backups');
    }

    private function uploadedArchive(): ?UploadedFileInterface
    {
        $upload = Input::file('restore_archive');

        return $upload instanceof UploadedFileInterface ? $upload : null;
    }
}
