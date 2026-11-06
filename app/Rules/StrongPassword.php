<?php

namespace App\Rules;

use App\Support\Auth\PasswordCriteria;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class StrongPassword implements ValidationRule
{
    /**
     * Uma só mensagem com tudo o que falta, pois a tela exibe um erro por campo.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $missing = array_filter(
            PasswordCriteria::all(),
            fn (array $criterion) => ! PasswordCriteria::meets($criterion, $value),
        );

        if ($missing !== []) {
            $fail(__('The password must have: :missing.', [
                'missing' => implode(', ', array_map(Str::lcfirst(...), array_column($missing, 'label'))),
            ]));
        }
    }
}
