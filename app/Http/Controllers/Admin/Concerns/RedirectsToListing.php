<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait RedirectsToListing
{
    /**
     * Volta à listagem mantendo filtros, ordem e página. Do `Referer` vem
     * somente a query: o destino é sempre a rota da listagem.
     */
    private function redirectToListing(Request $request, string $route): RedirectResponse
    {
        parse_str((string) parse_url((string) $request->headers->get('referer'), PHP_URL_QUERY), $query);

        return redirect()->route($route, $query);
    }
}
