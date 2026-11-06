<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAvatarRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileAvatarController extends Controller
{
    /**
     * O arquivo fica em disco privado. O endereço muda a cada imagem, por isso
     * o navegador pode guardá-la sem prazo.
     */
    public function show(Request $request): StreamedResponse
    {
        $path = $request->user()->personalData?->avatar_path;
        $disk = Storage::disk(config('template.profile.avatar_disk'));

        abort_unless($path !== null && $disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'private, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * A imagem anterior fica no disco, e o caminho dela, no histórico.
     */
    public function update(UpdateAvatarRequest $request): RedirectResponse
    {
        $path = $request->file('avatar')->store('avatars', config('template.profile.avatar_disk'));

        $request->user()->personalData()->updateOrCreate([], ['avatar_path' => $path]);
        $request->user()->unsetRelation('personalData');

        return to_route('profile.edit')->with('flash', ['type' => 'success', 'message' => __('Profile picture updated.')]);
    }

    /**
     * Remover só desfaz o vínculo: o arquivo não é apagado.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->personalData?->update(['avatar_path' => null]);
        $request->user()->unsetRelation('personalData');

        return to_route('profile.edit')->with('flash', ['type' => 'success', 'message' => __('Profile picture removed.')]);
    }
}
