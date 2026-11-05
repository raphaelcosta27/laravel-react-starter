<?php

namespace App\Actions;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Apaga os arquivos diários acima do prazo de retenção. A idade vem da data no
 * nome do arquivo, e tudo o que foge do padrão do canal "daily" fica intacto.
 */
class DeleteExpiredLogs
{
    private const DEFAULT_RETENTION_DAYS = 14;

    /** @return array{deleted: list<string>, failed: list<string>, retentionDays: int, invalidRetention: bool} */
    public function handle(): array
    {
        $configured = $this->configuredRetention();
        $retentionDays = $configured ?: self::DEFAULT_RETENTION_DAYS;
        $invalidRetention = $configured === false;

        if ($invalidRetention) {
            Log::warning('Invalid LOG_DAILY_DAYS value. Using the default retention.', ['days' => $retentionDays]);
        }

        $limit = today()->subDays($retentionDays)->toDateString();
        $deleted = [];
        $failed = [];

        foreach ($this->dailyFiles() as $path => $date) {
            if ($date >= $limit) {
                continue;
            }

            if (File::delete($path)) {
                $deleted[] = basename($path);
            } elseif (File::exists($path)) {
                $failed[] = basename($path);

                Log::error('Could not delete an expired log file.', ['file' => $path]);
            }
        }

        return [
            'deleted' => $deleted,
            'failed' => $failed,
            'retentionDays' => $retentionDays,
            'invalidRetention' => $invalidRetention,
        ];
    }

    /**
     * Dias configurados, nulo sem configuração e falso para um valor inválido.
     */
    private function configuredRetention(): int|false|null
    {
        $value = config('template.logs.retention_days');

        if ($value === null || $value === '') {
            return null;
        }

        return is_int($value) || is_string($value)
            ? filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : false;
    }

    /** @return array<string, string> Data de cada arquivo diário, pelo caminho. */
    private function dailyFiles(): array
    {
        $log = pathinfo(config('logging.channels.daily.path'));

        if (! File::isDirectory($log['dirname'])) {
            return [];
        }

        $pattern = sprintf(
            '/^%s-(\d{4})-(\d{2})-(\d{2})\.%s$/',
            preg_quote($log['filename'], '/'),
            preg_quote($log['extension'], '/'),
        );

        $files = [];

        foreach (File::files($log['dirname']) as $file) {
            if (preg_match($pattern, $file->getFilename(), $date) && checkdate((int) $date[2], (int) $date[3], (int) $date[1])) {
                $files[$file->getPathname()] = "{$date[1]}-{$date[2]}-{$date[3]}";
            }
        }

        return $files;
    }
}
