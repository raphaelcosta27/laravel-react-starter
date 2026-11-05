<?php

namespace App\Console\Commands;

use App\Actions\CreateUser;
use App\Models\User;
use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AppCreateUser extends Command
{
    protected $signature = 'app:create-user';

    protected $description = 'Cadastra um usuário com a conta já confirmada';

    public function handle(CreateUser $createUser): int
    {
        if (! $this->input->isInteractive()) {
            $this->error(__('This command needs an interactive terminal.'));

            return self::FAILURE;
        }

        $name = $this->askUntilValid(fn () => $this->ask(__('Name')), 'name', ['required', 'string', 'max:255']);

        $email = $this->askUntilValid(fn () => $this->ask(__('Email')), 'email', [
            'required', 'string', 'lowercase', 'email', 'max:255',
            Rule::unique(User::class)->whereNull('deleted_at'),
        ]);

        if (User::onlyTrashed()->where('email', $email)->exists()) {
            $createUser->handle($name, $email);

            $this->info(__('The deleted account of :email was reactivated. Check the access email.', ['email' => $email]));

            return self::SUCCESS;
        }

        $createUser->verified($name, $email, $this->askPassword());

        $this->info(__('User :email created with a confirmed account.', ['email' => $email]));

        return self::SUCCESS;
    }

    private function askPassword(): string
    {
        while (true) {
            $password = $this->askUntilValid(fn () => $this->secret(__('Password')), 'password', [
                'required', Password::defaults(),
            ]);

            if ($password === $this->secret(__('Confirm Password'))) {
                return $password;
            }

            $this->error(__('The passwords do not match.'));
        }
    }

    /**
     * Repete a pergunta até a resposta passar pelas mesmas regras do cadastro pela web.
     *
     * @param  array<int, mixed>  $rules
     */
    private function askUntilValid(Closure $ask, string $attribute, array $rules): string
    {
        while (true) {
            $value = (string) $ask();
            $validator = Validator::make([$attribute => $value], [$attribute => $rules]);

            if ($validator->passes()) {
                return $value;
            }

            $this->error($validator->errors()->first($attribute));
        }
    }
}
