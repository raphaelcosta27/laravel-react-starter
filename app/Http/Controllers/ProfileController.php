<?php

namespace App\Http\Controllers;

use App\Actions\Admin\DeleteUser;
use App\Actions\UpdateProfile;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\UserContact;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $data = $request->user()->personalData;

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'personalData' => [
                'preferred_name' => $data?->preferred_name,
                'birth_date' => $data?->birth_date?->format('Y-m-d'),
                'gender' => $data?->gender,
                'cpf' => $data?->cpf,
            ],
            'genders' => collect(config('template.profile.genders'))
                ->map(fn (string $gender) => ['value' => $gender, 'label' => __($gender)])
                ->values(),
            'cpfRequired' => (bool) config('template.profile.cpf_required'),
            'avatarMaxKb' => (int) config('template.profile.avatar_max_kb'),
            'contacts' => $request->user()->contacts()->orderBy('id')->get()
                ->map(fn (UserContact $contact) => [
                    'id' => $contact->id,
                    'type' => $contact->type,
                    'value' => $contact->value,
                    'is_whatsapp' => $contact->is_whatsapp,
                    'is_emergency' => $contact->is_emergency,
                    'relationship' => $contact->relationship,
                    'relationship_label' => $contact->relationship ? __($contact->relationship) : null,
                    'contact_name' => $contact->contact_name,
                ]),
            'relationships' => collect(config('template.profile.relationships'))
                ->map(fn (string $relationship) => ['value' => $relationship, 'label' => __($relationship)])
                ->values(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request, UpdateProfile $updateProfile): RedirectResponse
    {
        $updateProfile->handle($request->user(), $request->validated());

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request, DeleteUser $deleteUser): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $deleteUser->handle($request->user());

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
