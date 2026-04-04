<?php

declare(strict_types=1);

namespace App;

abstract class Controller
{
    /**
     * Render a view through the framework helper.
     *
     * @param array<string, mixed> $data
     */
    protected function render(string $tplFileName, array $data = []): mixed
    {
        return view($tplFileName, $data);
    }
}
