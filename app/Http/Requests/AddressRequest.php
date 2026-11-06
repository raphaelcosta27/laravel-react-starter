<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAddress;
use App\Models\UserAddress;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    use ValidatesAddress;

    private ?UserAddress $address = null;

    /**
     * Endereço de outra pessoa responde 404 antes da validação, para que a
     * resposta não dependa do que foi enviado.
     */
    public function authorize(): bool
    {
        $id = $this->route('address');

        if ($id !== null) {
            $this->address = $this->user()->addresses()->findOrFail($id);
        }

        return true;
    }

    public function address(): ?UserAddress
    {
        return $this->address;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeAddress();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->addressRules(),
            'is_main' => ['boolean'],
            'is_billing' => ['boolean'],
            'is_delivery' => ['boolean'],
            'recipient_name' => $this->boolean('is_delivery') ? ['required', 'string', 'max:150'] : ['exclude'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'postal_code.regex' => __('Enter a valid postal code.'),
            'state.in' => __('Choose a valid state.'),
        ];
    }
}
