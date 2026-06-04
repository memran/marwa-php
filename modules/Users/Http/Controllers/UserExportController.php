<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers;

use App\Modules\Users\Models\User;
use App\Modules\Users\Support\UserDataTable;
use App\Modules\Users\Support\UserRepository;
use App\Modules\Users\Support\UserStatus;
use App\Support\AdminListState;
use App\Support\DataTable\DataTableView;
use Laminas\Diactoros\Response as HttpResponse;
use Laminas\Diactoros\Stream;
use Marwa\Framework\Controllers\Controller;
use Psr\Http\Message\ResponseInterface;

final class UserExportController extends Controller
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AdminListState $listState,
        private readonly UserDataTable $userTable,
        private readonly DataTableView $dataTable,
    ) {}

    public function csv(): ResponseInterface
    {
        return $this->export('csv');
    }

    public function pdf(): ResponseInterface
    {
        return $this->export('pdf');
    }

    private function export(string $format): ResponseInterface
    {
        $state = $this->listState->state();
        $columns = $this->dataTable->normalizeVisibleColumns($this->userTable, request('columns', null));
        $status = UserStatus::tryFromFilter($state['filter']);
        $rows = $this->users->exportUsers($state['query'], $state['sort'], $state['direction'], $status);
        $filename = 'users-' . date('Ymd-His') . '.' . $format;

        if ($format === 'pdf') {
            return $this->downloadContent(
                $this->dataTable->buildPdf($this->userTable, $rows, $columns, $state),
                $filename,
                'application/pdf'
            );
        }

        return $this->downloadContent(
            $this->dataTable->buildCsv($this->userTable, $rows, $columns, $state),
            $filename,
            'text/csv; charset=UTF-8'
        );
    }

    private function downloadContent(string $content, string $filename, string $contentType): ResponseInterface
    {
        $stream = new Stream('php://temp', 'wb+');
        $stream->write($content);
        $stream->rewind();

        return new HttpResponse($stream, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="' . addcslashes($filename, '"\\') . '"',
        ]);
    }
}
