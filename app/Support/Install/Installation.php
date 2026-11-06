<?php

namespace App\Support\Install;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Instalado é quem tem usuário, mesmo excluído. O marcador só diz o que fazer
 * quando o banco não responde: uma queda não pode reabrir o assistente.
 */
class Installation
{
    private const TOKEN_MINUTES = 10;

    public function enabled(): bool
    {
        return (bool) config('template.install.enabled');
    }

    public function directory(): string
    {
        return config('template.install.directory');
    }

    public function isInstalled(): bool
    {
        try {
            $installed = User::withTrashed()->exists();
        } catch (QueryException $exception) {
            return $this->tableIsMissing($exception) ? false : is_file($this->path('installed'));
        } catch (Throwable) {
            return is_file($this->path('installed'));
        }

        if ($installed) {
            $this->mark();
        }

        return $installed;
    }

    public function codePath(): string
    {
        return $this->path('install-code.txt');
    }

    /**
     * O código fica no arquivo e no log, ao alcance só de quem acessa o servidor.
     */
    public function code(): string
    {
        return is_file($this->codePath()) ? trim((string) file_get_contents($this->codePath())) : $this->newCode();
    }

    public function newCode(): string
    {
        $code = Str::lower(Str::random(32));

        $this->write('install-code.txt', $code);

        Log::warning("Código de instalação: {$code}");

        return $code;
    }

    public function forgetCode(): void
    {
        File::delete($this->codePath());
    }

    public function issueToken(User $user): string
    {
        $token = Str::random(64);

        $this->write('install-finish', "{$user->id}|{$token}");

        return $token;
    }

    /**
     * Entrega o usuário uma única vez.
     */
    public function consumeToken(string $token): ?int
    {
        $path = $this->path('install-finish');

        if (! is_file($path)) {
            return null;
        }

        [$userId, $expected] = explode('|', (string) file_get_contents($path), 2) + [null, ''];
        $valid = filemtime($path) >= now()->subMinutes(self::TOKEN_MINUTES)->getTimestamp();

        if (! $valid || ! hash_equals((string) $expected, $token)) {
            return null;
        }

        File::delete($path);

        return (int) $userId;
    }

    private function mark(): void
    {
        if (! is_file($this->path('installed'))) {
            rescue(fn () => $this->write('installed', (string) now()), null, false);
        }
    }

    private function tableIsMissing(QueryException $exception): bool
    {
        return $exception->getCode() === '42S02' || str_contains($exception->getMessage(), 'no such table');
    }

    private function write(string $name, string $contents): void
    {
        File::ensureDirectoryExists($this->directory());
        File::put($this->path($name), $contents);
        @chmod($this->path($name), 0600);
    }

    private function path(string $name): string
    {
        return $this->directory().DIRECTORY_SEPARATOR.$name;
    }
}
