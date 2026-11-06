<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\ValidCpf;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $cpf = $this->input('cpf');

        if (is_string($cpf)) {
            $this->merge(['cpf' => preg_replace('/[.\-\s]/', '', $cpf) ?: null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'preferred_name' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:1900-01-01', 'before_or_equal:today'],
            'gender' => ['nullable', 'string', Rule::in(config('template.profile.genders'))],
            'cpf' => [
                'bail',
                config('template.profile.cpf_required') ? 'required' : 'nullable',
                'string',
                new ValidCpf,
                Rule::unique('user_dados', 'active_cpf')->ignore($this->user()->id, 'user_id'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'birth_date.*' => __('Enter a valid date of birth.'),
            'cpf.unique' => __('This CPF is already in use.'),
        ];
    }
}
