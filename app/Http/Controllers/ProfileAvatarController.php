<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAvatarRequest;
use App\Support\AvatarResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileAvatarController extends Controller
{
    public function show(Request $request): StreamedResponse
    {
        return AvatarResponse::for($request->user()->personalData?->avatar_path);
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
