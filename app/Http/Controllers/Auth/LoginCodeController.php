<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Auth\LoginCodeCheck;
use App\Support\Auth\LoginCodes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginCodeController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has('login.pending')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/LoginCode', [
            'minutes' => config('template.login.code_expire'),
            'status' => session('status'),
        ]);
    }

    public function store(Request $request, LoginCodes $codes): RedirectResponse
    {
        $pending = $request->session()->get('login.pending');

        if ($pending === null) {
            return redirect()->route('login');
        }

        $validated = $request->validate(['code' => ['required', 'digits:6']]);

        $user = User::find($pending['user']);
        $refusal = $this->refusal($user);

        if ($refusal !== null) {
            return $this->restart($request, trans($refusal));
        }

        return match ($codes->check($user->id, $pending['attempt'], $validated['code'])) {
            LoginCodeCheck::Valid => $this->complete($request, $user, $pending['remember']),
            LoginCodeCheck::Wrong => back()->withErrors(['code' => __('The code is incorrect.')]),
            LoginCodeCheck::Expired => back()->withErrors(['code' => __('This code is no longer valid. Request a new one.')]),
            LoginCodeCheck::Exhausted => $this->restart($request, __('Too many incorrect codes. Log in again.')),
        };
    }

    public function resend(Request $request, LoginCodes $codes): RedirectResponse
    {
        $pending = $request->session()->get('login.pending');

        if ($pending === null) {
            return redirect()->route('login');
        }

        $user = User::find($pending['user']);
        $refusal = $this->refusal($user);

        if ($refusal !== null) {
            return $this->restart($request, trans($refusal));
        }

        $request->session()->put('login.pending.attempt', $codes->issue($user, 'code'));

        return back()->with('status', __('A new code was sent to your email.'));
    }

    /** A conta pode ter mudado depois da senha. */
    private function refusal(?User $user): ?string
    {
        return $user === null ? 'auth.failed' : $user->loginRefusal();
    }

    private function complete(Request $request, User $user, bool $remember): RedirectResponse
    {
        $request->session()->forget('login.pending');

        Auth::login($user, $remember);

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function restart(Request $request, string $message): RedirectResponse
    {
        $request->session()->forget('login.pending');

        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
