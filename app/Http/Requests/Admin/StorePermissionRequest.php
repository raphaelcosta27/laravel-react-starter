<?php

namespace App\Http\Requests\Admin;

use App\Support\Permissions\Modules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermissionRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'code' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('permissions', 'code')->whereNull('deleted_at'),
            ],
            'description' => ['required', 'string', 'max:255'],
            'module' => ['required', Rule::in(Modules::keys())],
        ];
    }
}
