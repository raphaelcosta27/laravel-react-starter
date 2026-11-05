<?php

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChangeUserStatus
{
    public function __construct(private EnsureAdministratorRemains $ensureAdministratorRemains) {}

    public function handle(User $user, bool $active): void
    {
        DB::transaction(function () use ($user, $active) {
            if (! $active) {
                $this->ensureAdministratorRemains->handle($user);
            }

            $user->forceFill(['is_active' => $active])->save();
        });
    }
}
