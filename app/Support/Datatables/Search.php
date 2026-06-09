<?php

declare(strict_types=1);

namespace App\Support\Datatables;

final class Search
{
    /**
     * @param list<string> $columns
     */
    public function __construct(
        private string $term = '',
        private array $columns = [],
    ) {
        $this->term = trim($this->term);
        $this->columns = array_values(array_unique(array_filter($this->columns, static fn (string $column): bool => $column !== '')));
    }

    /**
     * @param list<string> $columns
     */
    public function columns(array $columns): self
    {
        $this->columns = array_values(array_unique(array_filter($columns, static fn (string $column): bool => $column !== '')));

        return $this;
    }

    public function term(string $term): self
    {
        $this->term = trim($term);

        return $this;
    }

    public function isActive(): bool
    {
        return $this->term !== '';
    }

    public function termValue(): string
    {
        return $this->term;
    }

    /**
     * @return list<string>
     */
    public function columnsValue(): array
    {
        return $this->columns;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'term' => $this->term,
            'columns' => $this->columns,
            'active' => $this->isActive(),
        ];
    }
}
