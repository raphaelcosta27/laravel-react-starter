<?php

namespace App\Http\Middleware;

use App\Support\Environment\EnvFile;
use App\Support\Install\Installation;
use Closure;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Roda antes de cookies e sessão: sem usuários, o sistema pode não ter arquivo de
 * ambiente, chave nem banco, e o assistente precisa abrir mesmo assim.
 */
class PrepareInstallation
{
    public function __construct(private Installation $installation, private EnvFile $file) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->installation->enabled() || $request->is('up') || $this->installation->isInstalled()) {
            return $next($request);
        }

        if (! $this->prepare()) {
            return response(__('The environment file cannot be written. Review the permissions of the application folder.'), 503);
        }

        return $request->is('install', 'install/*') ? $next($request) : redirect('/install');
    }

    private function prepare(): bool
    {
        $this->file->createFromExample();

        if (empty(config('app.key'))) {
            $key = 'base64:'.base64_encode(Encrypter::generateKey(config('app.cipher')));

            if (! $this->file->writable()) {
                return false;
            }

            $this->file->save(['APP_KEY' => $key]);

            config(['app.key' => $key]);
            app()->forgetInstance('encrypter');
        }

        foreach (['session.driver', 'cache.default'] as $setting) {
            if (config($setting) !== 'array') {
                config([$setting => 'file']);
            }
        }

        return true;
    }
}
