<?php

namespace App\Support\Environment;

use Dotenv\Dotenv;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Edita o arquivo de ambiente trocando só as linhas alteradas: comentários, ordem
 * e o resto ficam como estão. A gravação é completa ou não acontece.
 */
class EnvFile
{
    public function path(): string
    {
        return app()->environmentFilePath();
    }

    public function exists(): bool
    {
        return is_file($this->path());
    }

    public function contents(): string
    {
        return $this->exists() ? (string) file_get_contents($this->path()) : '';
    }

    /**
     * @return array<string, string>
     */
    public function values(): array
    {
        return array_map(fn (?string $value) => (string) $value, Dotenv::parse($this->contents()));
    }

    /**
     * Identifica o conteúdo sem permitir adivinhar o que há nele.
     */
    public function version(): string
    {
        return hash_hmac('sha256', $this->contents(), (string) config('app.key'));
    }

    public function writable(): bool
    {
        return $this->exists() ? is_writable($this->path()) : is_writable(dirname($this->path()));
    }

    public function createFromExample(): void
    {
        if (! $this->exists() && is_file(base_path('.env.example')) && $this->writable()) {
            copy(base_path('.env.example'), $this->path());
        }
    }

    /**
     * @param  array<string, string>  $changes
     *
     * @throws EnvFileConflict quando o arquivo mudou desde a versão informada
     * @throws RuntimeException quando não foi possível gravar
     */
    public function save(array $changes, ?string $version = null): void
    {
        File::ensureDirectoryExists(config('template.install.directory'));

        $lock = fopen(config('template.install.directory').DIRECTORY_SEPARATOR.'env.lock', 'c');

        if ($lock === false || ! flock($lock, LOCK_EX)) {
            throw new RuntimeException('Não foi possível travar o arquivo de ambiente.');
        }

        try {
            if ($version !== null && ! hash_equals($this->version(), $version)) {
                throw new EnvFileConflict;
            }

            $contents = $this->apply($this->contents(), $changes);

            $this->assertReadable($contents, $changes);
            $this->replace($contents);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * @param  array<string, string>  $changes
     */
    private function apply(string $contents, array $changes): string
    {
        $eol = $contents === '' ? PHP_EOL : (str_contains($contents, "\r\n") ? "\r\n" : "\n");

        foreach ($changes as $key => $value) {
            $line = "{$key}={$this->format($value)}";
            $pattern = '/^[ \t]*(?:export[ \t]+)?'.preg_quote($key, '/').'[ \t]*=[^\r\n]*/m';
            $count = 0;

            $contents = (string) preg_replace_callback($pattern, fn () => $line, $contents, 1, $count);

            if ($count === 0) {
                $contents = rtrim($contents, "\r\n").($contents === '' ? '' : $eol).$line.$eol;
            }
        }

        return $contents;
    }

    private function format(string $value): string
    {
        if (preg_match('/^[A-Za-z0-9_.:\/@+,;=\-]*$/', $value) === 1) {
            return $value;
        }

        return '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"';
    }

    /**
     * @param  array<string, string>  $changes
     */
    private function assertReadable(string $contents, array $changes): void
    {
        $parsed = Dotenv::parse($contents);

        foreach ($changes as $key => $value) {
            if ((string) ($parsed[$key] ?? '') !== $value) {
                throw new RuntimeException("O valor de {$key} não pôde ser gravado.");
            }
        }
    }

    private function replace(string $contents): void
    {
        $path = $this->path();
        $temporary = "{$path}.tmp";

        if ($this->exists()) {
            copy($path, config('template.install.directory').DIRECTORY_SEPARATOR.'.env.backup');
        }

        if (file_put_contents($temporary, $contents) === false || ! rename($temporary, $path)) {
            @unlink($temporary);

            throw new RuntimeException('Não foi possível gravar o arquivo de ambiente.');
        }
    }
}
