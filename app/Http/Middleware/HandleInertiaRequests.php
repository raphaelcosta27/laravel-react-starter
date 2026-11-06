<?php

namespace App\Http\Middleware;

use App\Support\Auth\PasswordCriteria;
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
            'branding' => [
                'logo' => config('template.branding.logo'),
                'primaryColor' => $this->color(config('template.branding.primary_color')),
                'secondaryColor' => $this->color(config('template.branding.secondary_color')),
            ],
            'auth' => $this->auth($request),
            'flash' => $request->hasSession() ? $request->session()->get('flash') : null,
            'passwordCriteria' => fn () => PasswordCriteria::all(),
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
            'displayName' => rescue(fn () => $user?->displayName(), null, false),
            'avatarUrl' => rescue(fn () => $user?->avatarUrl(), null, false),
            'isAdministrator' => $isAdministrator,
            'permissions' => $isAdministrator ? [] : rescue(fn () => $user?->effectivePermissions() ?? [], [], false),
        ];
    }

    /**
     * Sem uma cor hexadecimal válida, o tema usa o próprio padrão.
     */
    private function color(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? $value : null;
    }
}
