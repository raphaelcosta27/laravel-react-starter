<?php

namespace App\Http\Controllers;

use App\Actions\CreateUser;
use App\Actions\SaveEnvironment;
use App\Support\Environment\EnvCatalog;
use App\Support\Environment\EnvFile;
use App\Support\Install\Installation;
use Database\Seeders\AdminPanelSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Throwable;

/**
 * Cada passo pode ser repetido sem duplicar nada, e o progresso fica na sessão.
 * Nada além do código é respondido antes do desbloqueio.
 */
class InstallController extends Controller
{
    public function __construct(private Installation $installation) {}

    public function show(Request $request, EnvCatalog $catalog, EnvFile $file): Response
    {
        $code = $this->installation->code();
        $step = $this->step($request, $code);

        return Inertia::render('Install/Index', [
            'step' => $step,
            'codePath' => $step === 'unlock' ? 'storage/app/private/system/install-code.txt' : null,
            'groups' => $step === 'environment' ? $catalog->groups() : [],
            'writable' => $file->writable(),
        ]);
    }

    public function unlock(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string']]);
        $code = $this->installation->code();

        if (! hash_equals($code, trim($data['code']))) {
            throw ValidationException::withMessages(['code' => __('Wrong installation code.')]);
        }

        $request->session()->regenerate();
        $request->session()->put('install.unlocked', hash('sha256', $code));

        return to_route('install.show');
    }

    public function environment(Request $request, SaveEnvironment $saveEnvironment): RedirectResponse
    {
        $this->ensureUnlocked($request);

        $saveEnvironment->handle((array) $request->input('values', []), alwaysTestDatabase: true);

        $request->session()->put('install.environment', true);

        return to_route('install.show');
    }

    public function database(Request $request): RedirectResponse
    {
        $this->ensureUnlocked($request);
        abort_unless($request->session()->get('install.environment'), 409);

        try {
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--class' => AdminPanelSeeder::class, '--force' => true]);
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'database' => __('The database could not be prepared. See the application log and try again.'),
            ]);
        }

        $request->session()->put('install.database', true);

        return to_route('install.show');
    }

    public function user(Request $request, CreateUser $createUser): HttpResponse
    {
        $this->ensureUnlocked($request);
        abort_unless($request->session()->get('install.database'), 409);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $createUser->verified($data['name'], $data['email'], $data['password']);
        $user->forceFill(['must_change_password' => false])->save();

        $this->installation->forgetCode();
        $request->session()->forget('install');

        return Inertia::location(route('install.finish', $this->installation->issueToken($user)));
    }

    /**
     * Já fora do assistente: a sessão daqui é a definitiva, que pode ser de outro
     * tipo. O token vale uma vez.
     */
    public function finish(Request $request, string $token): RedirectResponse
    {
        $userId = $this->installation->consumeToken($token);

        abort_if($userId === null, 404);

        Auth::loginUsingId($userId);
        $request->session()->regenerate();

        return to_route('dashboard');
    }

    private function step(Request $request, string $code): string
    {
        return match (true) {
            ! $this->unlocked($request, $code) => 'unlock',
            ! $request->session()->get('install.environment') => 'environment',
            ! $request->session()->get('install.database') => 'database',
            default => 'user',
        };
    }

    private function ensureUnlocked(Request $request): void
    {
        abort_unless($this->unlocked($request, $this->installation->code()), 403);
    }

    /**
     * Um código novo invalida o desbloqueio anterior.
     */
    private function unlocked(Request $request, string $code): bool
    {
        return hash_equals(hash('sha256', $code), (string) $request->session()->get('install.unlocked'));
    }
}
