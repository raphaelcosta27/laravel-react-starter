<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Password::defaults(fn () => Password::min(8));

        $this->configurePermissions();
        $this->configureEmailLimits();
    }

    /**
     * Limita os envios por usuário de destino. Quem atinge o limite volta à
     * listagem com um aviso, em vez de uma página de erro.
     */
    private function configureEmailLimits(): void
    {
        RateLimiter::for('admin-emails', function (Request $request) {
            $user = $request->route('user');

            return Limit::perMinutes(config('template.admin.email_throttle_minutes'), config('template.admin.email_throttle'))
                ->by('admin-emails:'.($user instanceof User ? $user->getKey() : $user))
                ->response(fn () => back()->with('flash', [
                    'type' => 'warning',
                    'message' => __('Too many emails were sent to this user. Try again later.'),
                ]));
        });
    }

    /**
     * Administradores passam por toda verificação, inclusive de policies, sem
     * consultar permissões. Por isso, as proteções que valem também para eles
     * ficam nas Actions. Os demais dependem das permissões efetivas.
     */
    private function configurePermissions(): void
    {
        Gate::before(fn (User $user) => $user->isAdministrator() ? true : null);

        Gate::after(function (User $user, string $ability, ?bool $result) {
            return $result ?? (in_array($ability, $user->effectivePermissions(), true) ?: null);
        });
    }
}
