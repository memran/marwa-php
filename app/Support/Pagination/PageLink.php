<?php

declare(strict_types=1);

namespace App\Support\Pagination;

use JsonSerializable;

final readonly class PageLink implements JsonSerializable
{
    public function __construct(
        public ?int $number,
        public string $label,
        public ?string $url,
        public bool $active,
        public bool $disabled,
    ) {
    }

    /**
     * @return array{number:int|null,label:string,url:string|null,active:bool,disabled:bool}
     */
    public function toArray(): array
    {
        return [
            'number' => $this->number,
            'label' => $this->label,
            'url' => $this->url,
            'active' => $this->active,
            'disabled' => $this->disabled,
        ];
    }

    /**
     * @return array{number:int|null,label:string,url:string|null,active:bool,disabled:bool}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
