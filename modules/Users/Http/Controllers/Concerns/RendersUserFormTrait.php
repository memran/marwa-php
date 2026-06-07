<?php

declare(strict_types=1);

namespace App\Modules\Users\Http\Controllers\Concerns;

use App\Modules\Users\Support\UserFormData;

trait RendersUserFormTrait
{
    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function userFormViewData(UserFormData $forms, array $extra): array
    {
        $oldInput = $this->session('_old_input', []);
        $errors = $this->session('errors', []);

        return $forms->formViewData(
            $extra,
            is_array($oldInput) ? $oldInput : [],
            is_array($errors) ? $errors : []
        );
    }
}
