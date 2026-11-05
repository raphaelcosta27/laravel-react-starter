<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'branding' => ['logo' => config('template.branding.logo')],
            'auth' => $this->auth($request),
            'flash' => $request->hasSession() ? $request->session()->get('flash') : null,
            'translations' => app('translator')->getLoader()->load(app()->getLocale(), '*', '*'),
        ];
    }

    /**
     * Administradores recebem a lista vazia, pois têm acesso a qualquer código.
     * Estas props servem somente para exibir ou ocultar elementos.
     *
     * @return array<string, mixed>
     */
    private function auth(Request $request): array
    {
        $user = rescue(fn () => $request->user(), null, false);
        $isAdministrator = rescue(fn () => $user?->isAdministrator() ?? false, false, false);

        return [
            'user' => $user,
            'isAdministrator' => $isAdministrator,
            'permissions' => $isAdministrator ? [] : rescue(fn () => $user?->effectivePermissions() ?? [], [], false),
        ];
    }
}
