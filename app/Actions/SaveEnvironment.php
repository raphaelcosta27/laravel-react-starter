<?php

namespace App\Actions;

use App\Observers\ChangeRecorder;
use App\Support\Environment\DatabaseProbe;
use App\Support\Environment\EnvCatalog;
use App\Support\Environment\EnvFile;
use App\Support\Environment\EnvFileConflict;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SaveEnvironment
{
    public function __construct(
        private EnvCatalog $catalog,
        private EnvFile $file,
        private DatabaseProbe $probe,
        private ChangeRecorder $recorder,
    ) {}

    /**
     * Grava e faz os valores valerem na próxima requisição. Mudança de banco só
     * passa se a conexão nova responder.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function handle(array $input, ?string $version = null, bool $alwaysTestDatabase = false): void
    {
        $changes = $this->catalog->changes($input);
        $current = $this->file->values();

        /* O cookie de sessão deriva do nome: mudar um sem fixar o outro desloga todos. */
        if (isset($changes['APP_NAME']) && ! isset($current['SESSION_COOKIE'])) {
            $changes['SESSION_COOKIE'] = config('session.cookie');
        }

        if (! $this->file->writable()) {
            throw ValidationException::withMessages([
                'environment' => __('The environment file cannot be written. Review its permissions on the server.'),
            ]);
        }

        $this->testDatabase($current, $changes, $alwaysTestDatabase);

        if ($changes === []) {
            return;
        }

        try {
            $this->file->save($changes, $version);
        } catch (EnvFileConflict) {
            throw ValidationException::withMessages([
                'environment' => __('The environment file changed since this page was opened. Reload and try again.'),
            ]);
        } catch (RuntimeException) {
            throw ValidationException::withMessages([
                'environment' => __('The environment file could not be saved. Nothing was changed.'),
            ]);
        }

        $this->record($current, $changes);
        $this->flush();
    }

    /**
     * @param  array<string, string>  $current
     * @param  array<string, string>  $changes
     */
    private function testDatabase(array $current, array $changes, bool $always): void
    {
        $changed = array_filter(array_keys($changes), fn (string $key) => str_starts_with($key, 'DB_'));

        if (! $always && $changed === []) {
            return;
        }

        $failure = $this->probe->failure([...$current, ...$changes]);

        if ($failure !== null) {
            throw ValidationException::withMessages(['values.DB_HOST' => $failure]);
        }
    }

    /**
     * @param  array<string, string>  $current
     * @param  array<string, string>  $changes
     */
    private function record(array $current, array $changes): void
    {
        if (! auth()->check()) {
            return;
        }

        $items = [];

        foreach ($changes as $key => $value) {
            $items[$key] = [$current[$key] ?? null, $value];
        }

        $this->recorder->environment($items, $this->catalog->secretKeys());
    }

    private function flush(): void
    {
        foreach (['config:clear', 'cache:clear', 'route:clear'] as $command) {
            rescue(fn () => Artisan::call($command), null, false);
        }

        if (function_exists('opcache_reset')) {
            rescue(fn (): bool => @opcache_reset(), null, false);
        }
    }
}
