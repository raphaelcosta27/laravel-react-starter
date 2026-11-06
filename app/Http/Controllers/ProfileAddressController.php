<?php

namespace App\Http\Controllers;

use App\Actions\DeleteAddress;
use App\Actions\SaveAddress;
use App\Http\Requests\AddressRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileAddressController extends Controller
{
    public function store(AddressRequest $request, SaveAddress $saveAddress): RedirectResponse
    {
        $saveAddress->handle($request->user(), $request->validated());

        return $this->saved(__('Address added.'));
    }

    public function update(AddressRequest $request, SaveAddress $saveAddress): RedirectResponse
    {
        $saveAddress->handle($request->user(), $request->validated(), $request->address());

        return $this->saved(__('Address updated.'));
    }

    public function destroy(Request $request, string $address, DeleteAddress $deleteAddress): RedirectResponse
    {
        $deleteAddress->handle(
            $request->user(),
            $request->user()->addresses()->findOrFail($address),
            $request->input('new_main_id'),
        );

        return $this->saved(__('Address deleted.'));
    }

    private function saved(string $message): RedirectResponse
    {
        return to_route('profile.edit')->with('flash', ['type' => 'success', 'message' => $message]);
    }
}
