<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ChangeUserStatus;
use App\Actions\Admin\DeleteUser;
use App\Actions\Admin\RequestPasswordChange;
use App\Actions\Admin\RestoreUser;
use App\Actions\Admin\SendAuthenticationEmail;
use App\Actions\Admin\UpdateUser;
use App\Actions\CreateUser;
use App\Http\Controllers\Admin\Concerns\RedirectsToListing;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Profile;
use App\Models\User;
use App\Support\Tables\Definitions\UsersTable;
use App\Support\Tables\TableQuery;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    use RedirectsToListing;

    public function index(Request $request): Response
    {
        return $this->listing($request);
    }

    public function create(Request $request): Response
    {
        return $this->listing($request, fn () => [
            'mode' => 'create',
            'record' => null,
            'profileOptions' => $this->profileOptions($request->user()),
            'canChangeProfile' => true,
        ]);
    }

    public function store(StoreUserRequest $request, CreateUser $createUser): RedirectResponse
    {
        $profile = Profile::findOrFail($request->validated('profile_id'));

        Gate::authorize('create', [User::class, $profile]);

        $user = $createUser->handle($request->validated('name'), $request->validated('email'), profile: $profile);

        return $this->redirectToListing($request, 'admin.users.index')->with('flash', $this->creationMessage($user));
    }

    public function edit(Request $request, User $user): Response
    {
        Gate::authorize('update', $user);

        $actor = $request->user();

        return $this->listing($request, fn () => [
            'mode' => 'edit',
            'record' => $user->only('id', 'name', 'email', 'profile_id'),
            'profileOptions' => $this->profileOptions($actor, $user),
            'canChangeProfile' => $actor->isAdministrator() || $actor->id !== $user->id,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUser $updateUser): RedirectResponse
    {
        Gate::authorize('update', $user);

        $profileId = $request->validated('profile_id');

        if ($profileId !== null && (int) $profileId !== $user->profile_id) {
            Gate::authorize('changeProfile', [$user, Profile::findOrFail($profileId)]);
        }

        $updateUser->handle($user, $request->validated());

        return $this->redirectToListing($request, 'admin.users.index')->with('flash', $user->emailFailed
            ? ['type' => 'warning', 'message' => __('The user was saved, but the email could not be sent. Try again.')]
            : ['type' => 'success', 'message' => __('User saved.')]);
    }

    public function status(Request $request, User $user, ChangeUserStatus $changeUserStatus): RedirectResponse
    {
        Gate::authorize('changeStatus', $user);

        $validated = $request->validate(['is_active' => ['required', 'boolean']]);

        $changeUserStatus->handle($user, (bool) $validated['is_active']);

        return back()->with('flash', ['type' => 'success', 'message' => __('User status changed.')]);
    }

    public function destroy(User $user, DeleteUser $deleteUser): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $deleteUser->handle($user);

        return back()->with('flash', ['type' => 'success', 'message' => __('User deleted.')]);
    }

    public function restore(Request $request, User $user, RestoreUser $restoreUser): RedirectResponse
    {
        abort_unless($user->trashed(), 404);

        Gate::authorize('restore', $user);

        $validated = $request->validate([
            'profile_id' => ['nullable', 'integer', Rule::exists('profiles', 'id')->whereNull('deleted_at')],
        ]);

        $profile = isset($validated['profile_id']) ? Profile::find($validated['profile_id']) : null;

        if ($profile !== null) {
            Gate::authorize('changeProfile', [$user, $profile]);
        }

        $restoreUser->handle($user, $profile);

        return back()->with('flash', ['type' => 'success', 'message' => __('User restored.')]);
    }

    public function passwordChange(User $user, RequestPasswordChange $requestPasswordChange): RedirectResponse
    {
        Gate::authorize('requestPasswordChange', $user);

        return back()->with('flash', $this->emailMessage(
            $requestPasswordChange->handle($user),
            __('Password change requested.'),
        ));
    }

    public function authenticationEmail(User $user, SendAuthenticationEmail $sendAuthenticationEmail): RedirectResponse
    {
        Gate::authorize('sendAuthenticationEmail', $user);

        return back()->with('flash', $this->emailMessage(
            $sendAuthenticationEmail->handle($user),
            __('Authentication email sent.'),
        ));
    }

    private function listing(Request $request, ?Closure $form = null): Response
    {
        $actor = $request->user();

        return Inertia::render('Admin/Users/Index', [
            ...(new TableQuery(new UsersTable($actor), $request->query()))->lazyResponse($actor),
            'abilities' => fn () => [
                'create' => $actor->can('cadastrar_usuario'),
                'export' => $actor->can('exportar_dados'),
                'bulk' => [
                    ...($actor->can('alterar_situacao_usuario') ? ['activate', 'deactivate'] : []),
                    ...($actor->can('excluir_usuario') ? ['delete'] : []),
                ],
            ],
            'profiles' => fn () => Profile::orderBy('name')->get(['id', 'name'])
                ->map(fn (Profile $profile) => ['value' => (string) $profile->id, 'label' => $profile->name]),
            'form' => $form,
        ]);
    }

    /** @return array{type: string, message: string} */
    private function emailMessage(bool $sent, string $success): array
    {
        return $sent
            ? ['type' => 'success', 'message' => $success]
            : ['type' => 'warning', 'message' => __('The action was registered, but the email could not be sent. Try again.')];
    }

    /** @return array{type: string, message: string} */
    private function creationMessage(User $user): array
    {
        if ($user->wasReactivated) {
            return ['type' => 'warning', 'message' => $user->emailFailed
                ? __('This email belonged to a deleted account, which was reactivated, but the email could not be sent. Try again.')
                : __('This email belonged to a deleted account, which was reactivated. The access email was sent.')];
        }

        return $user->emailFailed
            ? ['type' => 'warning', 'message' => __('The user was saved, but the email could not be sent. Try again.')]
            : ['type' => 'success', 'message' => __('User saved.')];
    }

    /** @return Collection<int, array{value: string, label: string}> */
    private function profileOptions(User $actor, ?User $target = null): Collection
    {
        return Profile::orderBy('name')->get()
            ->filter(fn (Profile $profile) => $profile->id === $target?->profile_id || ($target === null
                ? $actor->can('create', [User::class, $profile])
                : $actor->can('changeProfile', [$target, $profile])))
            ->map(fn (Profile $profile) => ['value' => (string) $profile->id, 'label' => $profile->name])
            ->values();
    }
}
