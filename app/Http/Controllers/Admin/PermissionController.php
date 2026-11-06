<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\DeletePermission;
use App\Actions\Admin\RestorePermission;
use App\Http\Controllers\Admin\Concerns\RedirectsToListing;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePermissionRequest;
use App\Http\Requests\Admin\UpdatePermissionRequest;
use App\Models\Permission;
use App\Models\User;
use App\Support\Permissions\Modules;
use App\Support\Permissions\UserPermissionForm;
use App\Support\Tables\Definitions\PermissionsTable;
use App\Support\Tables\TableQuery;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PermissionController extends Controller
{
    use RedirectsToListing;

    public function index(Request $request): Response
    {
        return $this->listing($request);
    }

    public function create(Request $request): Response
    {
        return $this->listing($request, fn () => ['mode' => 'create', 'record' => null]);
    }

    public function store(StorePermissionRequest $request): RedirectResponse
    {
        Permission::create($request->validated());

        return $this->redirectToListing($request, 'admin.permissions.index')
            ->with('flash', ['type' => 'success', 'message' => __('Permission saved.')]);
    }

    public function edit(Request $request, Permission $permission): Response
    {
        return $this->listing($request, fn () => [
            'mode' => 'edit',
            'record' => $permission->only('id', 'title', 'code', 'description', 'module', 'is_system'),
        ]);
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): RedirectResponse
    {
        $permission->update($permission->is_system ? $request->safe()->except('module') : $request->validated());

        return $this->redirectToListing($request, 'admin.permissions.index')
            ->with('flash', ['type' => 'success', 'message' => __('Permission saved.')]);
    }

    public function destroy(Permission $permission, DeletePermission $deletePermission): RedirectResponse
    {
        $deletePermission->handle($permission);

        return back()->with('flash', ['type' => 'success', 'message' => __('Permission deleted.')]);
    }

    public function restore(Permission $permission, RestorePermission $restorePermission): RedirectResponse
    {
        abort_unless($permission->trashed(), 404);

        $restorePermission->handle($permission);

        return back()->with('flash', ['type' => 'success', 'message' => __('Permission restored.')]);
    }

    /** Caminho pelo módulo: não depende de Visualizar de Usuários. */
    public function userPermissions(Request $request, User $user): Response
    {
        Gate::authorize('managePermissions', $user);

        return $this->listing($request, fn () => UserPermissionForm::for($user, $request->user(), 'permissions'));
    }

    private function listing(Request $request, ?Closure $form = null): Response
    {
        $actor = $request->user();

        return Inertia::render('Admin/Permissions/Index', [
            ...(new TableQuery(new PermissionsTable($actor), $request->query()))->lazyResponse($actor),
            'abilities' => fn () => [
                'create' => $actor->can('cadastrar_permissao'),
                'export' => $actor->can('exportar_permissao'),
                'manageUsers' => $actor->can('gerenciar_permissoes_usuario'),
            ],
            'modules' => fn () => Modules::options(),
            'form' => $form,
        ]);
    }
}
