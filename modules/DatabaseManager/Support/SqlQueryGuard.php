<?php

declare(strict_types=1);

namespace App\Modules\DatabaseManager\Support;

final class SqlQueryGuard
{
    /**
     * @return array{query:string,normalized:string}
     */
    public function sanitize(string $query): array
    {
        $query = str_replace("\0", '', $query);
        $query = preg_replace("/\r\n?/", "\n", $query) ?? $query;
        $query = trim($query);

        if ($query === '') {
            throw new \InvalidArgumentException('Enter an SQL query before executing.');
        }

        if ($this->containsSqlComment($query)) {
            throw new \InvalidArgumentException('Inline SQL comments are not allowed in this console.');
        }

        $normalized = $this->trimTrailingSemicolon($query);

        if ($this->hasMultipleStatements($normalized)) {
            throw new \InvalidArgumentException('Only one SQL statement may be executed at a time.');
        }

        return [
            'query' => $query,
            'normalized' => $normalized,
        ];
    }

    public function requiresConfirmation(string $query): bool
    {
        $verb = $this->leadingVerb($query);

        return in_array($verb, [
            'update', 'delete', 'truncate', 'drop', 'alter', 'replace',
            'show', 'describe', 'explain', 'use'
        ], true);
    }

    public function statementType(string $query): string
    {
        return $this->leadingVerb($query);
    }

    private function trimTrailingSemicolon(string $query): string
    {
        $query = rtrim($query);

        while (str_ends_with($query, ';')) {
            $query = rtrim(substr($query, 0, -1));
        }

        return $query;
    }

    private function leadingVerb(string $query): string
    {
        if (preg_match('/^\s*([a-z]+)/i', $query, $matches) !== 1) {
            return 'unknown';
        }

        return strtolower($matches[1]);
    }

    private function containsSqlComment(string $query): bool
    {
        $length = strlen($query);
        $quote = null;
        $escape = false;

        for ($index = 0; $index < $length; $index++) {
            $char = $query[$index];
            $next = $index + 1 < $length ? $query[$index + 1] : null;

            if ($quote !== null) {
                if ($escape) {
                    $escape = false;
                    continue;
                }

                if ($char === '\\') {
                    $escape = true;
                    continue;
                }

                if ($char === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($char === '\'' || $char === '"') {
                $quote = $char;
                continue;
            }

            if (($char === '-' && $next === '-') || ($char === '/' && $next === '*')) {
                return true;
            }
        }

        return false;
    }

    private function hasMultipleStatements(string $query): bool
    {
        $length = strlen($query);
        $quote = null;
        $escape = false;

        for ($index = 0; $index < $length; $index++) {
            $char = $query[$index];

            if ($quote !== null) {
                if ($escape) {
                    $escape = false;
                    continue;
                }

                if ($char === '\\') {
                    $escape = true;
                    continue;
                }

                if ($char === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($char === '\'' || $char === '"') {
                $quote = $char;
                continue;
            }

            if ($char === ';') {
                return true;
            }
        }

        return false;
    }
}
