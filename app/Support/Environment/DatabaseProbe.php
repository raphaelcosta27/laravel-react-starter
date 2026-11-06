<?php

namespace App\Support\Environment;

use Illuminate\Support\Facades\DB;
use PDOException;
use Throwable;

/**
 * Testa uma conexão sem tocar a atual. A mensagem do banco fica aqui: quem chama
 * recebe um texto próprio, sem dado da conexão.
 */
class DatabaseProbe
{
    private const CONNECTION = 'environment_probe';

    /**
     * @param  array<string, string>  $values
     * @return string|null o motivo da falha
     */
    public function failure(array $values): ?string
    {
        $base = config('database.connections.'.($values['DB_CONNECTION'] ?? ''));

        if (! is_array($base)) {
            return __('This database type is not supported.');
        }

        $informed = [
            'host' => $values['DB_HOST'] ?? null,
            'port' => $values['DB_PORT'] ?? null,
            'database' => $values['DB_DATABASE'] ?? null,
            'username' => $values['DB_USERNAME'] ?? null,
            'password' => $values['DB_PASSWORD'] ?? null,
        ];

        config(['database.connections.'.self::CONNECTION => [
            ...$base,
            ...array_intersect_key($informed, $base),
            'url' => null,
        ]]);

        try {
            DB::connection(self::CONNECTION)->getPdo();

            return null;
        } catch (Throwable $exception) {
            return $this->reason($exception);
        } finally {
            DB::purge(self::CONNECTION);
            config(['database.connections.'.self::CONNECTION => null]);
        }
    }

    private function reason(Throwable $exception): string
    {
        $code = $exception instanceof PDOException ? ($exception->errorInfo[1] ?? null) : null;
        $code ??= $exception->getPrevious() instanceof PDOException ? ($exception->getPrevious()->errorInfo[1] ?? null) : null;

        return match ($code) {
            1049 => __('The database does not exist. Create it and try again.'),
            1044, 1045 => __('Access denied. Review the user and the password.'),
            default => __('Could not connect to the database. Review the server, the port and the name.'),
        };
    }
}
