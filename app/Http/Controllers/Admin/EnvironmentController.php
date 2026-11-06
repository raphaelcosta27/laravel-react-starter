<?php

namespace App\Http\Controllers\Admin;

use App\Actions\SaveEnvironment;
use App\Http\Controllers\Controller;
use App\Support\Environment\EnvCatalog;
use App\Support\Environment\EnvFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EnvironmentController extends Controller
{
    public function index(EnvCatalog $catalog, EnvFile $file): Response
    {
        return Inertia::render('Admin/Environment/Index', [
            'groups' => $catalog->groups(),
            'version' => $file->version(),
            'writable' => $file->writable(),
            'editable' => (bool) config('template.install.editor_enabled'),
        ]);
    }

    public function update(Request $request, SaveEnvironment $saveEnvironment): RedirectResponse
    {
        abort_unless(config('template.install.editor_enabled'), 403);

        $data = $request->validate([
            'values' => ['required', 'array'],
            'version' => ['required', 'string'],
        ]);

        $saveEnvironment->handle($data['values'], $data['version']);

        return to_route('admin.environment.index')
            ->with('flash', ['type' => 'success', 'message' => __('Environment saved.')]);
    }
}
