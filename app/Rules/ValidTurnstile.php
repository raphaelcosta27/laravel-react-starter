<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ValidTurnstile implements ValidationRule
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $response = Http::asForm()->timeout(5)->post(self::VERIFY_URL, [
                'secret' => config('services.turnstile.secret_key'),
                'response' => $value,
                'remoteip' => request()->ip(),
            ]);
        } catch (ConnectionException $e) {
            report($e);

            $fail(__('The verification service is unavailable. Try again later.'));

            return;
        }

        if ($response->serverError()) {
            $fail(__('The verification service is unavailable. Try again later.'));
        } elseif ($response->json('success') !== true) {
            $fail(__('The human verification failed. Try again.'));
        }
    }
}
