<?php

namespace App\Support\Dashboard\Blocks;

use App\Models\Profile;
use App\Models\User;
use App\Support\Dashboard\Block;

class ProfileDistribution implements Block
{
    public function key(): string
    {
        return 'ProfileDistribution';
    }

    public function title(): string
    {
        return __('Users by profile');
    }

    public function permission(): ?string
    {
        return 'visualizar_usuario';
    }

    public function available(): bool
    {
        return true;
    }

    public function data(User $user): array
    {
        return ['metrics' => Profile::query()
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn (Profile $profile) => [
                'key' => "profile_{$profile->id}",
                'label' => $profile->name,
                'value' => $profile->users_count,
                'url' => route('admin.users.index', ['tab' => 'all', 'filter' => ['profile_id' => [$profile->id]]]),
            ])
            ->all()];
    }
}
