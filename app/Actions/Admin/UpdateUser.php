<?php

namespace App\Actions\Admin;

use App\Actions\IssueTemporaryPassword;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateUser
{
    public function __construct(
        private EnsureAdministratorRemains $ensureAdministratorRemains,
        private IssueTemporaryPassword $issueTemporaryPassword,
    ) {}

    /**
     * Atende ao formulário e à edição direta. Trocar o e-mail exige nova
     * confirmação no novo endereço e mantém a senha atual.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): User
    {
        $emailChanged = DB::transaction(function () use ($user, $data) {
            if ($this->losesProfile($user, $data)) {
                $this->ensureAdministratorRemains->handle($user);
            }

            $user->fill($data);

            $emailChanged = $user->isDirty('email');

            if ($emailChanged) {
                $user->email_verified_at = null;
            }

            $user->save();

            return $emailChanged;
        });

        $user->unsetRelation('profile');

        if ($emailChanged) {
            $user->emailFailed = ! $this->issueTemporaryPassword->send($user);
        }

        return $user;
    }

    /** @param array<string, mixed> $data */
    private function losesProfile(User $user, array $data): bool
    {
        return isset($data['profile_id']) && (int) $data['profile_id'] !== $user->profile_id;
    }
}
