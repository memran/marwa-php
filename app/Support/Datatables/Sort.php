<?php

declare(strict_types=1);

namespace App\Support\Datatables;

final class Sort
{
    public function __construct(
        private string $field = '',
        private string $direction = 'asc',
    ) {
        $this->field = trim($this->field);
        $this->direction = strtolower(trim($this->direction)) === 'desc' ? 'desc' : 'asc';
    }

    public function field(string $field): self
    {
        $this->field = trim($field);

        return $this;
    }

    public function direction(string $direction): self
    {
        $this->direction = strtolower(trim($direction)) === 'desc' ? 'desc' : 'asc';

        return $this;
    }

    public function fieldValue(): string
    {
        return $this->field;
    }

    public function directionValue(): string
    {
        return $this->direction;
    }

    public function isActive(): bool
    {
        return $this->field !== '';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'direction' => $this->direction,
            'active' => $this->isActive(),
        ];
    }
}
