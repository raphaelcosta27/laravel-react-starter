<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

/**
 * Regras do componente de endereço, para qualquer formulário que o use.
 */
trait ValidatesAddress
{
    private const BRAZILIAN_STATES = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA',
        'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
    ];

    protected function isBrazil(): bool
    {
        $country = $this->input('country');

        return is_string($country) && in_array(mb_strtolower(trim($country)), ['brasil', 'brazil', 'br'], true);
    }

    protected function normalizeAddress(): void
    {
        $postalCode = $this->input('postal_code');

        if (is_string($postalCode) && $this->isBrazil()) {
            $this->merge(['postal_code' => preg_replace('/[.\-\s]/', '', $postalCode)]);
        }
    }

    /**
     * @return array<string, array<mixed>>
     */
    protected function addressRules(): array
    {
        return [
            'postal_code' => ['required', 'string', $this->isBrazil() ? 'regex:/^\d{8}$/' : 'max:20'],
            'street' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:150'],
            'neighborhood' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', $this->isBrazil() ? Rule::in(self::BRAZILIAN_STATES) : 'max:100'],
            'country' => ['required', 'string', 'max:100'],
        ];
    }
}
