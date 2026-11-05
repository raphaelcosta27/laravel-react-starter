<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ChangeUserStatus;
use App\Actions\Admin\DeleteUser;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserBulkActionController extends Controller
{
    /**
     * Cada registro passa pela mesma policy e pela mesma Action da operação
     * individual. Os recusados são ignorados, e os demais são processados.
     */
    public function __invoke(Request $request, ChangeUserStatus $changeUserStatus, DeleteUser $deleteUser): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['activate', 'deactivate', 'delete'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $action = $validated['action'];
        $ability = $action === 'delete' ? 'delete' : 'changeStatus';
        $users = User::whereKey($validated['ids'])->get();
        $processed = 0;

        foreach ($users as $user) {
            if (Gate::denies($ability, $user)) {
                continue;
            }

            try {
                match ($action) {
                    'activate' => $changeUserStatus->handle($user, true),
                    'deactivate' => $changeUserStatus->handle($user, false),
                    'delete' => $deleteUser->handle($user),
                };

                $processed++;
            } catch (ValidationException) {
                continue;
            }
        }

        $skipped = count($validated['ids']) - $processed;

        return back()->with('flash', [
            'type' => $skipped === 0 ? 'success' : 'warning',
            'message' => __('Processed: :processed. Skipped: :skipped.', ['processed' => $processed, 'skipped' => $skipped]),
        ]);
    }
}
