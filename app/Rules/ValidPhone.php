<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Espera somente os dígitos, sem a máscara: DDD existente seguido de celular
 * (9 e mais oito dígitos) ou fixo (2 a 5 e mais sete dígitos).
 */
class ValidPhone implements ValidationRule
{
    private const PATTERN = '/^(?:1[1-9]|2[12478]|3[1-578]|4[1-9]|5[1345]|6[1-9]|7[134579]|8[1-9]|9[1-9])(?:9\d{8}|[2-5]\d{7})$/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match(self::PATTERN, $value) !== 1) {
            $fail(__('Enter a valid phone number with area code.'));
        }
    }
}
