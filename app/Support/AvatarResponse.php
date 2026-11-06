<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AvatarResponse
{
    /**
     * O arquivo fica em disco privado. O endereço muda a cada imagem, por isso
     * o navegador pode guardá-la sem prazo.
     */
    public static function for(?string $path): StreamedResponse
    {
        $disk = Storage::disk(config('template.profile.avatar_disk'));

        abort_unless($path !== null && $disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'private, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
