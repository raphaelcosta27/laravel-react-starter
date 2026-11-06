<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\DeleteProfile;
use App\Actions\Admin\RestoreProfile;
use App\Actions\Admin\SyncProfilePermissions;
use App\Http\Controllers\Admin\Concerns\RedirectsToListing;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProfileRequest;
use App\Http\Requests\Admin\UpdateProfileRequest;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use App\Support\Permissions\Modules;
use App\Support\Tables\Definitions\ProfilesTable;
use App\Support\Tables\Definitions\ProfileUsersTable;
use App\Support\Tables\TableQuery;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
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
            'modules' => $this->modules($request->user()),
            'selected' => [],
        ]);
    }

    public function store(StoreProfileRequest $request, SyncProfilePermissions $sync): RedirectResponse
    {
        Gate::authorize('setPermissions', [new Profile, $request->validated('permissions', [])]);

        DB::transaction(function () use ($request, $sync) {
            $profile = Profile::create($request->safe()->only(['name', 'description']));

            $sync->handle($profile, $request->validated('permissions', []));
        });

        return $this->redirectToListing($request, 'admin.profiles.index')
            ->with('flash', ['type' => 'success', 'message' => __('Profile saved.')]);
    }

    public function edit(Request $request, Profile $profile): Response
    {
        Gate::authorize('update', $profile);

        return $this->listing($request, fn () => [
            'mode' => 'edit',
            'record' => [
                ...$profile->only('id', 'name', 'description'),
                'is_administrator' => $profile->isAdministrator(),
            ],
            'modules' => $this->modules($request->user()),
            'selected' => $profile->profilePermissions()->pluck('permission_id'),
        ]);
    }

    public function update(UpdateProfileRequest $request, Profile $profile, SyncProfilePermissions $sync): RedirectResponse
    {
        Gate::authorize('update', $profile);
        Gate::authorize('setPermissions', [$profile, $request->validated('permissions', [])]);

        DB::transaction(function () use ($request, $profile, $sync) {
            $profile->update($request->safe()->only(['name', 'description']));

            if ($request->has('permissions')) {
                $sync->handle($profile, $request->validated('permissions', []));
            }
        });

        return $this->redirectToListing($request, 'admin.profiles.index')
            ->with('flash', ['type' => 'success', 'message' => __('Profile saved.')]);
    }

    public function destroy(Profile $profile, DeleteProfile $deleteProfile): RedirectResponse
    {
        Gate::authorize('delete', $profile);

        $deleteProfile->handle($profile);

        return back()->with('flash', ['type' => 'success', 'message' => __('Profile deleted.')]);
    }

    public function restore(Profile $profile, RestoreProfile $restoreProfile): RedirectResponse
    {
        abort_unless($profile->trashed(), 404);

        Gate::authorize('restore', $profile);

        $restoreProfile->handle($profile);

        return back()->with('flash', ['type' => 'success', 'message' => __('Profile restored.')]);
    }

    public function users(Request $request, Profile $profile): Response
    {
        return Inertia::render('Admin/Profiles/Users', [
            ...(new TableQuery(new ProfileUsersTable($profile), $request->query()))->response($request->user()),
            'profile' => $profile->only('id', 'name'),
        ]);
    }

    private function listing(Request $request, ?Closure $form = null): Response
    {
        $actor = $request->user();

        return Inertia::render('Admin/Profiles/Index', [
            ...(new TableQuery(new ProfilesTable($actor), $request->query()))->lazyResponse($actor),
            'abilities' => fn () => [
                'create' => $actor->can('cadastrar_perfil'),
                'export' => $actor->can('exportar_perfil'),
            ],
            'form' => $form,
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function modules(User $actor): array
    {
        return Modules::groups(Permission::all(), fn (Permission $permission) => [
            ...$permission->only('id', 'title', 'code', 'description'),
            'available' => $actor->holdsPermissions([$permission->code]),
        ]);
    }
}
