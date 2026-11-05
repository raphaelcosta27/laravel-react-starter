<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Idempotente: procura também em registros excluídos, para não duplicar nem
 * desfazer o que foi alterado depois da primeira execução.
 */
class AdminPanelSeeder extends Seeder
{
    public function run(): void
    {
        $administrator = Profile::administrator();
        $default = Profile::default();

        $this->createPermissions();
        $this->assignAdministrator($administrator);

        User::withTrashed()->whereNull('profile_id')->update(['profile_id' => $default->id]);
    }

    private function createPermissions(): void
    {
        foreach (trans('permissions', [], 'pt_BR') as $code => $texts) {
            if (Permission::withTrashed()->where('code', $code)->exists()) {
                continue;
            }

            $permission = new Permission(['code' => $code, ...$texts]);
            $permission->is_system = true;
            $permission->save();
        }
    }

    private function assignAdministrator(Profile $administrator): void
    {
        if (User::withTrashed()->where('profile_id', $administrator->id)->exists()) {
            return;
        }

        User::where('is_active', true)
            ->orderBy('created_at')
            ->orderBy('id')
            ->first()
            ?->update(['profile_id' => $administrator->id]);
    }
}
