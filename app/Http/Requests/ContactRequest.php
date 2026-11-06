<?php

namespace App\Http\Requests;

use App\Models\UserContact;
use App\Rules\ValidPhone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ContactRequest extends FormRequest
{
    private ?UserContact $contact = null;

    /**
     * Contato de outra pessoa responde 404 antes da validação, para que a
     * resposta não dependa do que foi enviado.
     */
    public function authorize(): bool
    {
        $id = $this->route('contact');

        if ($id !== null) {
            $this->contact = $this->user()->contacts()->findOrFail($id);
        }

        return true;
    }

    public function contact(): ?UserContact
    {
        return $this->contact;
    }

    protected function prepareForValidation(): void
    {
        $value = $this->input('value');

        if (is_string($value)) {
            $this->merge([
                'value' => $this->isPhone() ? preg_replace('/[()\-\s]/', '', $value) : Str::lower($value),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $emergency = $this->boolean('is_emergency');

        return [
            'type' => ['required', 'string', Rule::in([UserContact::TYPE_PHONE, UserContact::TYPE_EMAIL])],
            'value' => [
                'bail',
                'required',
                'string',
                'max:255',
                $this->isPhone() ? new ValidPhone : 'email:filter',
                Rule::unique('user_contatos', 'active_value')
                    ->where('user_id', $this->user()->id)
                    ->ignore($this->contact?->id),
            ],
            'is_whatsapp' => ['boolean'],
            'is_emergency' => ['boolean'],
            'relationship' => $emergency
                ? ['required', 'string', Rule::in(config('template.profile.relationships'))]
                : ['exclude'],
            'contact_name' => $emergency ? ['required', 'string', 'max:150'] : ['exclude'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'value.email' => __('Enter a valid email address.'),
            'value.unique' => __('You already have this contact.'),
        ];
    }

    private function isPhone(): bool
    {
        return $this->input('type') === UserContact::TYPE_PHONE;
    }
}
