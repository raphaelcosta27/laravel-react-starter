<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\SyncUserPermissions;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserPermissionController extends Controller
{
    public function edit(Request $request, User $user): Response
    {
        Gate::authorize('managePermissions', $user);

        return Inertia::render('Admin/Users/Permissions', [
            'user' => $user->only('id', 'name'),
            'permissions' => $this->permissions($user, $request->user()),
            'canAdjust' => ! $user->isAdministrator(),
        ]);
    }

    public function update(Request $request, User $user, SyncUserPermissions $sync): RedirectResponse
    {
        Gate::authorize('managePermissions', $user);

        $validated = $request->validate([
            'adjustments' => ['present', 'array'],
            'adjustments.*.permission_id' => ['required', 'integer', Rule::exists('permissions', 'id')->whereNull('deleted_at')],
            'adjustments.*.effect' => ['required', Rule::in([UserPermission::GRANT, UserPermission::REVOKE])],
        ]);

        Gate::authorize('adjustPermissions', [$user, $validated['adjustments']]);

        $sync->handle($user, $validated['adjustments']);

        return back()->with('flash', ['type' => 'success', 'message' => __('User permissions saved.')]);
    }

    /**
     * Cada permissão com seu estado efetivo e sua origem.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function permissions(User $user, User $actor): Collection
    {
        $isAdministrator = $user->isAdministrator();
        $effective = $user->effectivePermissions();
        $adjustments = $user->permissionAdjustments()->pluck('effect', 'permission_id');

        return Permission::orderBy('title')->get()->map(function (Permission $permission) use ($actor, $isAdministrator, $effective, $adjustments) {
            $adjustment = $adjustments->get($permission->id);
            $active = $isAdministrator || in_array($permission->code, $effective, true);

            return [
                ...$permission->only('id', 'title', 'code'),
                'active' => $active,
                'adjustment' => $adjustment,
                'origin' => $adjustment ?? ($active ? 'profile' : 'none'),
                'available' => $actor->holdsPermissions([$permission->code]),
            ];
        });
    }
}
