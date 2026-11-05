<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Str;

class CreateUser
{
    public function __construct(private IssueTemporaryPassword $issueTemporaryPassword) {}

    /**
     * Ponto único de criação de usuários. Com $temporary falso, a senha foi
     * escolhida pelo próprio usuário: não expira e não é repetida no e-mail.
     */
    public function handle(string $name, string $email, ?string $password = null, bool $temporary = true): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password ?? Str::random(40),
        ]);

        if ($temporary) {
            $this->issueTemporaryPassword->handle($user, $password);
        } else {
            $this->issueTemporaryPassword->send($user);
        }

        return $user;
    }

    /**
     * Cria uma conta já confirmada. Sem nada a confirmar, nenhum e-mail é enviado.
     */
    public function verified(string $name, string $email, string $password): User
    {
        $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }
}
