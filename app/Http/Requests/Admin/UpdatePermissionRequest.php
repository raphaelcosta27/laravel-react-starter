<?php

namespace App\Http\Requests\Admin;

use App\Support\Permissions\Modules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermissionRequest extends FormRequest
{
    /**
     * O código fica de fora: depois de criado, ele não muda. Dados parciais
     * atendem à edição direta na tabela.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['sometimes', 'required', 'string', 'max:255'],
            'module' => ['sometimes', 'nullable', Rule::in(Modules::keys())],
        ];
    }
}
