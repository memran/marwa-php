<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use Marwa\Framework\Controllers\Controller;
use Marwa\Framework\Security\RiskAnalyzer;
use Psr\Http\Message\ResponseInterface;

final class SecurityRiskReportController extends Controller
{
    public function __construct(
        private readonly RiskAnalyzer $riskAnalyzer,
    ) {}

    public function index(): ResponseInterface
    {
        $sinceHours = $this->positiveInt(request('since_hours', 24), 24, 1, 8760);
        $report = $this->riskAnalyzer->report($sinceHours);

        return $this->view('@security/risk', [
            'enabled' => $this->riskAnalyzer->enabled(),
            'log_path' => $this->riskAnalyzer->logPath(),
            'since_hours' => $sinceHours,
            'report' => $this->formatReport($report),
        ]);
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
