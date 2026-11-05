<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\DeleteProfile;
use App\Actions\Admin\RestoreProfile;
use App\Actions\Admin\SyncProfilePermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProfileRequest;
use App\Http\Requests\Admin\UpdateProfileRequest;
use App\Models\Permission;
use App\Models\Profile;
use App\Support\Tables\Definitions\ProfilesTable;
use App\Support\Tables\Definitions\ProfileUsersTable;
use App\Support\Tables\TableQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = $request->user();

        return Inertia::render('Admin/Profiles/Index', [
            ...(new TableQuery(new ProfilesTable($actor), $request->query()))->response($actor),
            'abilities' => [
                'create' => $actor->can('cadastrar_perfil'),
                'export' => $actor->can('exportar_dados'),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Profiles/Form', [
            'profile' => null,
            'permissions' => $this->permissions(),
            'selected' => [],
        ]);
    }

    public function store(StoreProfileRequest $request, SyncProfilePermissions $sync): RedirectResponse
    {
        DB::transaction(function () use ($request, $sync) {
            $profile = Profile::create($request->safe()->only(['name', 'description']));

            $sync->handle($profile, $request->validated('permissions', []));
        });

        return redirect()->route('admin.profiles.index')
            ->with('flash', ['type' => 'success', 'message' => __('Profile saved.')]);
    }

    public function edit(Profile $profile): Response
    {
        Gate::authorize('update', $profile);

        return Inertia::render('Admin/Profiles/Form', [
            'profile' => [
                ...$profile->only('id', 'name', 'description'),
                'is_administrator' => $profile->isAdministrator(),
            ],
            'permissions' => $this->permissions(),
            'selected' => $profile->profilePermissions()->pluck('permission_id'),
        ]);
    }

    public function update(UpdateProfileRequest $request, Profile $profile, SyncProfilePermissions $sync): RedirectResponse
    {
        Gate::authorize('update', $profile);

        DB::transaction(function () use ($request, $profile, $sync) {
            $profile->update($request->safe()->only(['name', 'description']));

            if ($request->has('permissions')) {
                $sync->handle($profile, $request->validated('permissions', []));
            }
        });

        return back()->with('flash', ['type' => 'success', 'message' => __('Profile saved.')]);
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

    /** @return Collection<int, Permission> */
    private function permissions(): Collection
    {
        return Permission::orderBy('title')->get(['id', 'title', 'code']);
    }
}
