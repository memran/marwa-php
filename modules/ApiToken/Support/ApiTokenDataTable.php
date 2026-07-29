<?php

declare(strict_types=1);

namespace App\Modules\ApiToken\Support;

use App\Modules\ApiToken\Models\ApiToken;
use App\Support\Datatables\Action;
use App\Support\Datatables\Column;
use App\Support\Datatables\DataTable;
use App\Support\Datatables\Filter;
use Psr\Http\Message\ServerRequestInterface;

final class ApiTokenDataTable
{
    public function make(ServerRequestInterface $request): DataTable
    {
        return DataTable::fromRequest($request)
            ->query(ApiToken::query())
            ->title('API tokens')
            ->description('Search, filter, and inspect tokens used for external service access.')
            ->searchPlaceholder('Search API tokens...')
            ->searchAriaLabel('Search API tokens')
            ->searchParameter('q')
            ->sortParameter('sort')
            ->directionParameter('direction')
            ->pageParameter('page')
            ->filterParameter('filters')
            ->columnsParameter('columns')
            ->path('/admin/api-tokens')
            ->rowKey('id')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Column::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->link(static fn (ApiToken $token): string => '/admin/api-tokens/show/' . $token->getKey()),
                Column::make('token_prefix')
                    ->label('Token')
                    ->searchable()
                    ->format(static fn (mixed $value): string => (string) $value . '...'),
                Column::make('rate_limit')
                    ->label('Rate limit')
                    ->sortable()
                    ->align('right')
                    ->format(static fn (mixed $value): string => (int) $value . '/min'),
                Column::make('allowed_ips')
                    ->label('Allowed IPs')
                    ->searchable()
                    ->format(static function (mixed $value): string {
                        $ips = is_string($value) ? json_decode($value, true) : $value;
                        if (!is_array($ips) || $ips === []) {
                            return 'Any';
                        }

                        return implode(', ', array_map(static fn (mixed $ip): string => (string) $ip, $ips));
                    }),
                Column::make('is_active')
                    ->label('Status')
                    ->filterable()
                    ->format(static fn (mixed $value): string => (int) $value === 1 ? 'Active' : 'Revoked')
                    ->badge(static fn (mixed $value): array => $value === 'Active'
                        ? ['tone' => 'success', 'label' => 'Active']
                        : ['tone' => 'danger', 'label' => 'Revoked']),
                Column::make('last_used_at')
                    ->label('Last used')
                    ->sortable()
                    ->format(static fn (mixed $value): string => trim((string) $value) !== '' ? (string) $value : 'Never'),
                Column::make('created_at')
                    ->label('Created')
                    ->sortable(),
            ])
            ->filters([
                Filter::select('status')
                    ->label('Status')
                    ->options([
                        'all' => 'All',
                        'active' => 'Active',
                        'revoked' => 'Revoked',
                    ])
                    ->default('all')
                    ->apply(static function ($query, mixed $value): void {
                        if ($value === 'active') {
                            $query->where('is_active', '=', 1);
                            return;
                        }

                        if ($value === 'revoked') {
                            $query->where('is_active', '=', 0);
                        }
                    }),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->permission('api_token.view')
                    ->url(static fn (ApiToken $token): string => '/admin/api-tokens/show/' . $token->getKey()),
            ])
            ->row(static function (ApiToken $token): array {
                return [
                    'cells' => [
                        'token_prefix' => [
                            'type' => 'text',
                            'value' => $token->getMaskedToken(),
                            'muted' => true,
                        ],
                    ],
                ];
            });
    }
}
