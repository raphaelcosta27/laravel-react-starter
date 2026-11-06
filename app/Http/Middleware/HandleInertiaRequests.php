<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Auth\PasswordCriteria;
use App\Support\ScreenNotifications;
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
                'logoDark' => config('template.branding.logo_dark') ?: null,
                'tagline' => config('template.branding.tagline') ?: null,
                'primaryColor' => $this->color(config('template.branding.primary_color')),
                'secondaryColor' => $this->color(config('template.branding.secondary_color')),
            ],
            'auth' => $this->auth($request),
            'notifications' => fn () => $this->notifications($request),
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
            'theme' => $user === null ? null : ($user->theme ?? User::THEME_SYSTEM),
            'isAdministrator' => $isAdministrator,
            'permissions' => $isAdministrator ? [] : rescue(fn () => $user?->effectivePermissions() ?? [], [], false),
        ];
    }

    /**
     * Desligadas ou sem login, a tela não mostra o sino.
     *
     * @return array{unread: int, pollSeconds: int}|null
     */
    private function notifications(Request $request): ?array
    {
        $user = rescue(fn () => $request->user(), null, false);

        if ($user === null || ! config('template.notifications.enabled')) {
            return null;
        }

        return [
            'unread' => rescue(fn () => ScreenNotifications::unread($user), 0, false),
            'pollSeconds' => (int) config('template.notifications.poll_seconds'),
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
