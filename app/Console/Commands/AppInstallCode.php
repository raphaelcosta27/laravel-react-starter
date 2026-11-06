<?php

namespace App\Console\Commands;

use App\Support\Install\Installation;
use Illuminate\Console\Command;

class AppInstallCode extends Command
{
    protected $signature = 'app:install-code';

    protected $description = 'Gera um novo código de instalação, invalidando o anterior';

    public function handle(Installation $installation): int
    {
        if ($installation->isInstalled()) {
            $this->error(__('The system is already installed.'));

            return self::FAILURE;
        }

        $this->line($installation->newCode());

        return self::SUCCESS;
    }
}
