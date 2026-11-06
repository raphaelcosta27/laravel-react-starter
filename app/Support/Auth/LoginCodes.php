<?php

namespace App\Support\Auth;

use App\Models\User;
use App\Notifications\LoginCode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Códigos do login em duas etapas. Ficam no cache, como dado técnico de curta
 * duração: uma entrada por usuário faz valer somente o código mais recente.
 */
class LoginCodes
{
    /**
     * Gera e envia um código. Devolve o identificador da tentativa, que prende
     * o código à sessão que o pediu.
     *
     * @param  string  $field  Campo da tela que recebe o erro.
     *
     * @throws ValidationException
     */
    public function issue(User $user, string $field): string
    {
        $this->countSending($user, $field);

        $attempt = Str::random(40);
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addMinutes(config('template.login.code_expire'))->getTimestamp();

        Cache::put($this->key($user->id), [
            'attempt' => $attempt,
            'hash' => $this->hash($attempt, $code),
            'tries' => 0,
            'expires_at' => $expiresAt,
        ], $expiresAt - now()->getTimestamp());

        try {
            $user->notify(new LoginCode($code));
        } catch (Throwable $e) {
            report($e);

            Cache::forget($this->key($user->id));

            throw ValidationException::withMessages([
                $field => __('The verification code could not be sent. Try again.'),
            ]);
        }

        return $attempt;
    }

    /** A trava impede que pedidos paralelos passem do limite de tentativas. */
    public function check(int $userId, string $attempt, string $code): LoginCodeCheck
    {
        $key = $this->key($userId);

        return Cache::lock("{$key}:lock", 10)->block(5, function () use ($key, $attempt, $code) {
            $record = Cache::get($key);

            if ($record === null || $record['attempt'] !== $attempt) {
                return LoginCodeCheck::Expired;
            }

            if (hash_equals($record['hash'], $this->hash($attempt, $code))) {
                Cache::forget($key);

                return LoginCodeCheck::Valid;
            }

            $tries = $record['tries'] + 1;

            if ($tries >= config('template.login.code_attempts')) {
                Cache::forget($key);

                return LoginCodeCheck::Exhausted;
            }

            Cache::put($key, ['tries' => $tries] + $record, $record['expires_at'] - now()->getTimestamp());

            return LoginCodeCheck::Wrong;
        });
    }

    /**
     * Conta login e reenvio juntos. Sem isso, quem tem a senha renovaria as
     * tentativas sem fim, e a conta receberia e-mails sem limite.
     *
     * @throws ValidationException
     */
    private function countSending(User $user, string $field): void
    {
        $limiter = "login-code-sending:{$user->id}";

        if (RateLimiter::tooManyAttempts($limiter, config('template.login.code_send_limit'))) {
            throw ValidationException::withMessages([
                $field => __('Too many codes were requested. Try again in :minutes minutes.', [
                    'minutes' => (int) ceil(RateLimiter::availableIn($limiter) / 60),
                ]),
            ]);
        }

        RateLimiter::hit($limiter, config('template.login.code_expire') * 60);
    }

    private function key(int $userId): string
    {
        return "login-code:{$userId}";
    }

    /** Com a chave da aplicação: 6 dígitos cairiam por força bruta num hash sem segredo. */
    private function hash(string $attempt, string $code): string
    {
        return hash_hmac('sha256', "{$attempt}|{$code}", config('app.key'));
    }
}
