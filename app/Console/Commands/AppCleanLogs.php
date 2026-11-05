<?php

namespace App\Console\Commands;

use App\Actions\DeleteExpiredLogs;
use Illuminate\Console\Command;

class AppCleanLogs extends Command
{
    protected $signature = 'app:clean-logs';

    protected $description = 'Apaga os arquivos de log acima do prazo de retenção';

    public function handle(DeleteExpiredLogs $deleteExpiredLogs): int
    {
        $result = $deleteExpiredLogs->handle();

        if ($result['invalidRetention']) {
            $this->warn(__('Invalid LOG_DAILY_DAYS value. Using the default retention.'));
        }

        $this->info(__(':count log file(s) deleted. Retention: :days days.', [
            'count' => count($result['deleted']),
            'days' => $result['retentionDays'],
        ]));

        foreach ($result['failed'] as $file) {
            $this->error(__('Could not delete :file.', ['file' => $file]));
        }

        return $result['failed'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
