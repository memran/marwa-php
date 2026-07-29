<?php

declare(strict_types=1);

namespace App\Modules\ApiToken\Support;

use Marwa\Entity\Entity\EntitySchema;
use Marwa\Entity\Form\FormBuilder;

final class ApiTokenFormData
{
    /**
     * @param array<string, mixed> $values
     * @param array<string, mixed> $errors
     * @return array{fields:array<string, array<string, mixed>>,values:array<string, mixed>,errors:array<string, list<string>>}
     */
    public function context(array $values = [], array $errors = []): array
    {
        $schema = EntitySchema::make('api_tokens');
        $schema->string('name')
            ->label('Token name')
            ->meta('required', true)
            ->meta('placeholder', 'e.g. Production API')
            ->meta('autocomplete', 'off');
        $schema->integer('rate_limit')
            ->label('Rate limit per minute')
            ->meta('required', true)
            ->meta('min', 1)
            ->meta('max', 10000)
            ->meta('step', 1)
            ->meta('hint', 'Maximum requests per minute. Range: 1-10000.');
        $schema->string('allowed_ips')
            ->label('Allowed IP addresses')
            ->meta('widget', 'textarea')
            ->meta('rows', 4)
            ->meta('wrapper_class', 'md:col-span-2')
            ->meta('placeholder', "192.168.1.1\n10.0.0.0/24")
            ->meta('hint', 'One IP address or CIDR per line. Leave empty to allow any IP address.');

        return (new FormBuilder($schema))->context(
            array_replace(['name' => '', 'rate_limit' => 60, 'allowed_ips' => ''], $values),
            $this->normalizeErrors($errors),
        );
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'allowed_ips' => 'nullable|string',
            'rate_limit' => 'required|integer|min:1|max:10000',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Token name is required.',
            'rate_limit.required' => 'Rate limit is required.',
            'rate_limit.min' => 'Rate limit must be at least 1 request per minute.',
            'rate_limit.max' => 'Rate limit may not exceed 10000 requests per minute.',
        ];
    }

    /**
     * @param array<string, mixed> $validated
     * @return array{name:string, allowed_ips:list<string>, allowed_ips_text:string, invalid_ips:list<string>, rate_limit:int}
     */
    public function normalize(array $validated): array
    {
        $allowedIpsText = trim((string) ($validated['allowed_ips'] ?? ''));
        $parsedIps = $this->parseAllowedIps($allowedIpsText);

        return [
            'name' => trim((string) ($validated['name'] ?? '')),
            'allowed_ips' => $parsedIps['valid'],
            'allowed_ips_text' => $allowedIpsText,
            'invalid_ips' => $parsedIps['invalid'],
            'rate_limit' => (int) ($validated['rate_limit'] ?? 60),
        ];
    }

    /**
     * @return array{valid:list<string>, invalid:list<string>}
     */
    private function parseAllowedIps(string $allowedIpsText): array
    {
        if ($allowedIpsText === '') {
            return ['valid' => [], 'invalid' => []];
        }

        $valid = [];
        $invalid = [];

        foreach (preg_split('/\R/', $allowedIpsText) ?: [] as $line) {
            $entry = trim($line);

            if ($entry === '') {
                continue;
            }

            if ($this->isValidIpOrCidr($entry)) {
                $valid[] = $entry;
                continue;
            }

            $invalid[] = $entry;
        }

        return [
            'valid' => array_values(array_unique($valid)),
            'invalid' => $invalid,
        ];
    }

    private function isValidIpOrCidr(string $entry): bool
    {
        if (filter_var($entry, FILTER_VALIDATE_IP) !== false) {
            return true;
        }

        $parts = explode('/', $entry);

        if (count($parts) !== 2) {
            return false;
        }

        [$subnet, $mask] = $parts;

        if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return false;
        }

        if (!ctype_digit($mask)) {
            return false;
        }

        $maskValue = (int) $mask;

        return $maskValue >= 0 && $maskValue <= 32;
    }

    /**
     * @param array<string, mixed> $errors
     * @return array<string, list<string>>
     */
    private function normalizeErrors(array $errors): array
    {
        $normalized = [];

        foreach ($errors as $field => $messages) {
            if (is_string($messages)) {
                $normalized[(string) $field] = [$messages];
                continue;
            }

            if (!is_array($messages)) {
                continue;
            }

            $normalized[(string) $field] = array_values(array_map(
                static fn (mixed $message): string => (string) $message,
                array_filter($messages, static fn (mixed $message): bool => is_scalar($message)),
            ));
        }

        return $normalized;
    }
}
