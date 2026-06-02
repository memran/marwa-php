<?php

declare(strict_types=1);

namespace App\Support\Export;

use Closure;

final class Column
{
    /**
     * @param Closure(mixed $row): string $value
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly Closure $value,
    ) {}

    public static function make(string $key, string $label, Closure $value): self
    {
        return new self($key, $label, $value);
    }

    public function resolve(mixed $row): string
    {
        return (string) ($this->value)($row);
    }
}
