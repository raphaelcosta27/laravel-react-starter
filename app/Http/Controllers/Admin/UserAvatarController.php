<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AvatarResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserAvatarController extends Controller
{
    /**
     * A conta excluída leva os dados pessoais junto, e a foto dela continua
     * aparecendo na aba de excluídos.
     */
    public function show(User $user): StreamedResponse
    {
        return AvatarResponse::for($user->personalData()->withTrashed()->value('avatar_path'));
    }
}
