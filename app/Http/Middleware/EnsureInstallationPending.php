<?php

namespace App\Http\Middleware;

use App\Support\Install\Installation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstallationPending
{
    public function __construct(private Installation $installation) {}

    public function handle(Request $request, Closure $next): Response
    {
        abort_if(! $this->installation->enabled() || $this->installation->isInstalled(), 404);

        return $next($request);
    }
}
