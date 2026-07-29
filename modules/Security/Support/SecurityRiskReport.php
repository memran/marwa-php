<?php

declare(strict_types=1);

namespace App\Modules\Security\Support;

use Marwa\Framework\Security\RiskAnalyzer;

final class SecurityRiskReport
{
    public function __construct(
        private readonly RiskAnalyzer $riskAnalyzer,
        private readonly SecurityRiskDataTable $table = new SecurityRiskDataTable(),
    ) {}

    /**
     * @return array{
     *     enabled: bool,
     *     log_path: string,
     *     since_hours: int,
     *     report: array{
     *         total: int,
     *         byCategory: array<string, int>,
     *         byScore: array{high: int, medium: int, low: int},
     *         latest: list<array<string, mixed>>
     *     }
     *     table: \App\Support\Datatables\Contracts\DataTableResultInterface
     * }
     */
    /**
     * @param array<string, mixed> $filters
     */
    public function viewData(mixed $sinceHours, mixed $page = 1, mixed $search = '', array $filters = []): array
    {
        $hours = $this->positiveInt($sinceHours, 24, 1, 8760);
        $currentPage = $this->positiveInt($page, 1, 1, PHP_INT_MAX);
        $searchTerm = is_scalar($search) ? trim((string) $search) : '';
        $report = $this->formatReport($this->riskAnalyzer->report($hours));

        return [
            'enabled' => $this->riskAnalyzer->enabled(),
            'log_path' => $this->riskAnalyzer->logPath(),
            'since_hours' => $hours,
            'report' => $report,
            'table' => $this->table->make($report['latest'], $currentPage, per_page(20), $hours, $searchTerm, $filters),
        ];
    }

    private function positiveInt(mixed $value, int $default, int $min, int $max): int
    {
        if (!is_scalar($value) || !is_numeric($value)) {
            return $default;
        }

        $number = (int) $value;

        if ($number < $min) {
            return $default;
        }

        return min($number, $max);
    }

    /**
     * @param array{
     *     total: int,
     *     byCategory: array<string, int>,
     *     byScore: array{high: int, medium: int, low: int},
     *     latest: list<array<string, mixed>>
     * } $report
     * @return array{
     *     total: int,
     *     byCategory: array<string, int>,
     *     byScore: array{high: int, medium: int, low: int},
     *     latest: list<array<string, mixed>>
     * }
     */
    private function formatReport(array $report): array
    {
        $report['latest'] = array_map(
            static function (array $entry): array {
                $entry['context_display'] = json_encode(
                    $entry['context'] ?? [],
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                ) ?: '{}';

                return $entry;
            },
            $report['latest']
        );

        return $report;
    }
}
