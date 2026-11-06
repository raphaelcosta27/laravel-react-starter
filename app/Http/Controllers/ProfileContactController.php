<?php

namespace App\Http\Controllers;

use App\Actions\SaveContact;
use App\Http\Requests\ContactRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileContactController extends Controller
{
    public function store(ContactRequest $request, SaveContact $saveContact): RedirectResponse
    {
        $saveContact->handle($request->user(), $request->validated());

        return $this->saved(__('Contact added.'));
    }

    public function update(ContactRequest $request, SaveContact $saveContact): RedirectResponse
    {
        $saveContact->handle($request->user(), $request->validated(), $request->contact());

        return $this->saved(__('Contact updated.'));
    }

    public function destroy(Request $request, string $contact): RedirectResponse
    {
        $request->user()->contacts()->findOrFail($contact)->delete();

        return $this->saved(__('Contact deleted.'));
    }

    private function saved(string $message): RedirectResponse
    {
        return to_route('profile.edit')->with('flash', ['type' => 'success', 'message' => $message]);
    }
}
