<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\Auth\LoginCodes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'canRegister' => config('template.registration_enabled'),
            'captchaSiteKey' => config('services.turnstile.enabled') ? config('services.turnstile.site_key') : null,
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request, LoginCodes $codes): RedirectResponse
    {
        $user = $request->authenticate();
        $remember = $request->boolean('remember');

        if (config('template.login.two_factor_enabled')) {
            $attempt = $codes->issue($user, 'email');

            $request->session()->regenerate();
            $request->session()->put('login.pending', [
                'user' => $user->id,
                'attempt' => $attempt,
                'remember' => $remember,
            ]);

            return redirect()->route('login.code');
        }

        Auth::login($user, $remember);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
