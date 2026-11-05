<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApplicationIsConfigured
{
    /**
     * Responde antes do roteamento: sem APP_KEY, os middlewares de cookies e
     * sessão falhariam, inclusive depois de enviar a resposta.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (empty(config('app.key'))) {
            return response()->view('errors.setup-required', [], 500);
        }

        return $next($request);
    }
}
