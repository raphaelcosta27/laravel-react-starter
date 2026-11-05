<?php

namespace App\Http\Controllers\Auth;

use App\Actions\IssueTemporaryPassword;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccessRequestController extends Controller
{
    /**
     * Display the new authentication request screen.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/RequestAccess', ['status' => session('status')]);
    }

    /**
     * Issue a new temporary password. The answer is always the same, so it
     * does not reveal which emails exist, and a valid password is never replaced.
     */
    public function store(Request $request, IssueTemporaryPassword $issueTemporaryPassword): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::firstWhere('email', $request->email);

        if ($user?->is_active && $user->temporaryPasswordExpired()) {
            $issueTemporaryPassword->handle($user);
        }

        return back()->with('status', __('If your temporary password has expired, a new one is on its way to your email.'));
    }
}
