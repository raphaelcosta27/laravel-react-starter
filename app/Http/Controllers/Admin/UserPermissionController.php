<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\SyncUserPermissions;
use App\Http\Controllers\Admin\Concerns\RedirectsToListing;
use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UserPermissionController extends Controller
{
    use RedirectsToListing;

    private const SEARCH_LIMIT = 20;

    private const LISTINGS = [
        'users' => 'admin.users.index',
        'permissions' => 'admin.permissions.index',
    ];

    public function update(Request $request, User $user, SyncUserPermissions $sync): RedirectResponse
    {
        Gate::authorize('managePermissions', $user);

        $validated = $request->validate([
            'origin' => ['sometimes', Rule::in(array_keys(self::LISTINGS))],
            'adjustments' => ['present', 'array'],
            'adjustments.*.permission_id' => ['required', 'integer', Rule::exists('permissions', 'id')->whereNull('deleted_at')],
            'adjustments.*.effect' => ['required', Rule::in([UserPermission::GRANT, UserPermission::REVOKE])],
        ]);

        Gate::authorize('adjustPermissions', [$user, $validated['adjustments']]);

        $sync->handle($user, $validated['adjustments']);

        return $this->redirectToListing($request, self::LISTINGS[$validated['origin'] ?? 'users'])
            ->with('flash', ['type' => 'success', 'message' => __('User permissions saved.')]);
    }

    /** Quem usa o módulo Permissões pode não ter acesso à listagem de usuários. */
    public function search(Request $request): JsonResponse
    {
        $actor = $request->user();
        $term = trim((string) $request->query('search'));

        $users = User::query()
            ->whereKeyNot($actor->id)
            ->when(! $actor->isAdministrator(), fn ($query) => $query->where(fn ($others) => $others
                ->whereNull('profile_id')
                ->orWhere('profile_id', '!=', Profile::administrator()->id)))
            ->when($term !== '', fn ($query) => $query->where(fn ($matching) => $matching
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")))
            ->orderBy('name')
            ->limit(self::SEARCH_LIMIT)
            ->get(['id', 'name', 'email']);

        return response()->json($users->map->only('id', 'name', 'email'));
    }
}
