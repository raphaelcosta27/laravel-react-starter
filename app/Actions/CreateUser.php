<?php

namespace App\Actions;

use App\Actions\Admin\ReactivateUser;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateUser
{
    public function __construct(
        private IssueTemporaryPassword $issueTemporaryPassword,
        private ReactivateUser $reactivateUser,
    ) {}

    /**
     * Ponto único de criação de usuários. Com $temporary falso, a senha foi
     * escolhida pelo próprio usuário: não expira e não é repetida no e-mail.
     * O e-mail de uma conta excluída reativa essa conta em vez de criar outra.
     */
    public function handle(
        string $name,
        string $email,
        ?string $password = null,
        bool $temporary = true,
        ?Profile $profile = null,
    ): User {
        $deleted = User::onlyTrashed()->firstWhere('email', $email);

        if ($deleted !== null) {
            return $this->reactivateUser->handle($deleted, $name, $profile ?? Profile::default());
        }

        $user = $this->store(new User([
            'name' => $name,
            'email' => $email,
            'password' => $password ?? Str::random(40),
        ]), $profile);

        $user->emailFailed = ! ($temporary
            ? $this->issueTemporaryPassword->handle($user, $password)
            : $this->issueTemporaryPassword->send($user));

        return $user;
    }

    /**
     * Cria uma conta já confirmada. Sem nada a confirmar, nenhum e-mail é enviado.
     */
    public function verified(string $name, string $email, string $password, ?Profile $profile = null): User
    {
        $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
        $user->email_verified_at = now();

        return $this->store($user, $profile);
    }

    /**
     * O bloqueio na linha do perfil Administrador garante um único primeiro usuário.
     */
    private function store(User $user, ?Profile $profile): User
    {
        return DB::transaction(function () use ($user, $profile) {
            $administrator = Profile::whereKey(Profile::administrator()->id)->lockForUpdate()->first();

            $user->profile_id = User::withTrashed()->exists()
                ? ($profile ?? Profile::default())->id
                : $administrator->id;

            $user->save();

            return $user;
        });
    }
}
