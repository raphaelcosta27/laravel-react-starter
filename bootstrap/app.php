<?php

use App\Http\Middleware\EnsureApplicationIsConfigured;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureRegistrationEnabled;
use App\Http\Middleware\EnsureTemporaryPasswordValid;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(EnsureApplicationIsConfigured::class);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => $request->hasSession() && $request->session()->has('login.pending')
            ? route('login.code')
            : route('login'));

        $middleware->alias([
            'account.active' => EnsureUserIsActive::class,
            'password.changed' => EnsurePasswordChanged::class,
            'registration.enabled' => EnsureRegistrationEnabled::class,
            'temporary.password' => EnsureTemporaryPasswordValid::class,
        ]);

        $middleware->group('account.ready', [
            'auth',
            'auth.session',
            'account.active',
            'temporary.password',
            'verified',
            'password.changed',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['cpf']);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (InvalidSignatureException $e, Request $request) {
            if ($request->routeIs('verification.verify')) {
                return redirect()->route('verification.notice')
                    ->with('status', 'verification-link-expired');
            }
        });

        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $status = $response->getStatusCode();

            $errorPage = fn (array $props = []) => Inertia::render('Error', [
                ...(new HandleInertiaRequests)->share($request),
                'status' => $status,
                ...$props,
            ])->toResponse($request)->setStatusCode($status);

            if ($status === 403 && $request->routeIs('verification.verify')) {
                return $errorPage([
                    'message' => __('This link belongs to another account. Log out and sign in with the correct account.'),
                    'canLogout' => true,
                ]);
            }

            if (config('app.debug') || ! in_array($status, [403, 404, 419, 429, 500, 503])) {
                return $response;
            }

            return $errorPage();
        });
    })->create();
