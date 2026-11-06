<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAvatarRequest extends FormRequest
{
    /**
     * O formato vem do conteúdo do arquivo, e não da extensão informada.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'avatar' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:'.config('template.profile.avatar_max_kb')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $wrongFormat = __('Choose a JPG, PNG or WebP image.');
        $tooLarge = __('The picture must be at most :size MB.', [
            'size' => round(config('template.profile.avatar_max_kb') / 1024, 1),
        ]);

        return [
            'avatar.required' => $wrongFormat,
            'avatar.file' => $wrongFormat,
            'avatar.mimes' => $wrongFormat,
            'avatar.max' => $tooLarge,
            'avatar.uploaded' => $tooLarge,
        ];
    }
}
