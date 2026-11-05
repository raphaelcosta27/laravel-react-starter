<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\DeletePermission;
use App\Actions\Admin\RestorePermission;
use App\Http\Controllers\Admin\Concerns\RedirectsToListing;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePermissionRequest;
use App\Http\Requests\Admin\UpdatePermissionRequest;
use App\Models\Permission;
use App\Support\Tables\Definitions\PermissionsTable;
use App\Support\Tables\TableQuery;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'record' => $permission->only('id', 'title', 'code', 'description'),
        ]);
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): RedirectResponse
    {
        $permission->update($request->validated());

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

    private function listing(Request $request, ?Closure $form = null): Response
    {
        $actor = $request->user();

        return Inertia::render('Admin/Permissions/Index', [
            ...(new TableQuery(new PermissionsTable($actor), $request->query()))->lazyResponse($actor),
            'abilities' => fn () => [
                'create' => $actor->can('cadastrar_permissao'),
                'export' => $actor->can('exportar_dados'),
            ],
            'form' => $form,
        ]);
    }
}
