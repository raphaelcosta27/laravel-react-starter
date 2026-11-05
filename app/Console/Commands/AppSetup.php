<?php

namespace App\Console\Commands;

use App\Actions\CreateUser;
use App\Models\User;
use Database\Seeders\AdminPanelSeeder;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Throwable;

class AppSetup extends Command
{
    /** Códigos do MySQL para servidor inacessível ou acesso negado. */
    private const CONNECTION_ERRORS = [1044, 1045, 2002, 2003, 2006];

    protected $signature = 'app:setup';

    protected $description = 'Prepara a aplicação: chave, banco de dados e usuário inicial';

    public function handle(CreateUser $createUser): int
    {
        if (! file_exists($this->laravel->environmentFilePath())) {
            $this->error(__('Configuration file not found. Copy .env.example to .env and try again.'));

            return self::FAILURE;
        }

        if (empty(config('app.key'))) {
            $this->call('key:generate', ['--force' => true]);
        }

        try {
            $this->callSilently('migrate', ['--force' => true]);
        } catch (Throwable $e) {
            $this->error($this->isConnectionFailure($e)
                ? __('Could not reach the database. Check if MySQL is running and review DB_HOST, DB_PORT, DB_USERNAME and DB_PASSWORD in .env.')
                : __('Could not apply the migrations: :message', ['message' => $e->getMessage()]));

            return self::FAILURE;
        }

        $this->callSilently('db:seed', ['--class' => AdminPanelSeeder::class, '--force' => true]);

        $this->info(__('Database ready.'));

        $this->createInitialUser($createUser);

        return self::SUCCESS;
    }

    private function createInitialUser(CreateUser $createUser): void
    {
        ['name' => $name, 'email' => $email, 'password' => $password] = config('template.initial_user');

        if (empty($email)) {
            $this->warn(__('Initial user not created: set INITIAL_USER_EMAIL in .env.'));

            return;
        }

        if (User::withTrashed()->where('email', $email)->exists()) {
            $this->info(__('Initial user already exists and was kept.'));

            return;
        }

        $createUser->handle($name, $email, $password ?: null);

        $this->info(__('Initial user created. Check the email sent to :email.', ['email' => $email]));
    }

    private function isConnectionFailure(Throwable $e): bool
    {
        $code = $e instanceof QueryException ? ($e->errorInfo[1] ?? null) : null;

        return in_array($code, self::CONNECTION_ERRORS, true);
    }
}
