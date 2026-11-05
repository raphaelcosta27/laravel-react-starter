<?php

namespace App\Support\Tables\Definitions;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ProfileUsersTable extends UsersTable
{
    public function __construct(private Profile $profile, ?User $actor = null)
    {
        parent::__construct($actor);
    }

    public function query(): Builder
    {
        return parent::query()->where('profile_id', $this->profile->id);
    }

    public function tabs(): array
    {
        return [];
    }
}
