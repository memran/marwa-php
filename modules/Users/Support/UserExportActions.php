<?php

declare(strict_types=1);

namespace App\Modules\Users\Support;

use App\Modules\Users\Models\User;
use App\Support\AdminListState;
use App\Support\Export\Exporter;
use Marwa\Router\Response;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class UserExportActions
{
    public function __construct(
        private readonly UsersTableData $tableData,
        private readonly UserListing $listing,
        private readonly UsersRequestParams $params,
        private readonly AdminListState $listState,
    ) {}

    public function exportCsv(): ResponseInterface
    {
        return $this->buildDownload('csv', 'users-export.csv', self::CSV_HEADERS);
    }

    public function exportPdf(): ResponseInterface
    {
        return $this->buildDownload('pdf', 'users-export.pdf', self::PDF_HEADERS);
    }

    /**
     * @param array<string, string> $headers
     */
    private function buildDownload(string $format, string $filename, array $headers): ResponseInterface
    {
        $context = $this->buildContext();
        $tempFile = $this->writeTempFile($context, $format);

        if ($tempFile === null) {
            return Response::html('Unable to generate export file.', 500);
        }

        $this->scheduleCleanup($tempFile);

        return Response::download($tempFile, $filename, $headers);
    }

    /**
     * @return array{users:list<User>, columns:list<string>, title:string}
     */
    private function buildContext(): array
    {
        $params = $this->params->listParams();
        $state = $this->listState->stateFrom($params, 'q', 'status', 'sort', 'direction', 'page');
        $status = UserStatus::tryFromFilter($state['filter']);
        $visibleColumns = $this->tableData->normalizeVisibleColumns($params['columns'] ?? null);
        $users = $this->listing->listUsers(
            $state['query'],
            $status,
            $state['sort'],
            $state['direction']
        );

        return [
            'users' => $users,
            'columns' => $visibleColumns,
            'title' => 'Registered users',
        ];
    }

    /**
     * @param array{users:list<User>, columns:list<string>, title:string} $context
     */
    private function writeTempFile(array $context, string $format): ?string
    {
        $tempFile = tempnam(sys_get_temp_dir(), "users-export-{$format}-");

        if ($tempFile === false) {
            return null;
        }

        try {
            $this->writePayload($tempFile, $context, $format);
        } catch (Throwable) {
            if (is_file($tempFile)) {
                unlink($tempFile);
            }
            return null;
        }

        return $tempFile;
    }

    /**
     * @param array{users:list<User>, columns:list<string>, title:string} $context
     */
    private function writePayload(string $tempFile, array $context, string $format): void
    {
        if ($format === 'pdf') {
            $this->tableData->writePdfToFile(
                $tempFile,
                $context['users'],
                $context['columns'],
                $context['title']
            );
            return;
        }

        $this->tableData->writeCsvToFile($tempFile, $context['users'], $context['columns']);
    }

    private function scheduleCleanup(string $tempFile): void
    {
        register_shutdown_function(static function (string $path): void {
            if (is_file($path)) {
                @unlink($path);
            }
        }, $tempFile);
    }

    private const CSV_HEADERS = [
        'Content-Type' => 'text/csv; charset=UTF-8',
        'Cache-Control' => 'no-store, no-cache, must-revalidate',
    ];

    private const PDF_HEADERS = [
        'Content-Type' => 'application/pdf',
        'Cache-Control' => 'no-store, no-cache, must-revalidate',
    ];
}
