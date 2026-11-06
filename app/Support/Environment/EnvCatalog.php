<?php

namespace App\Support\Environment;

use Dotenv\Dotenv;
use Illuminate\Validation\ValidationException;

/**
 * Parâmetros do exemplo documentado e do arquivo atual, prontos para a tela.
 * Valor de segredo nunca sai daqui.
 */
class EnvCatalog
{
    private const LOCKED = ['APP_KEY', 'APP_PREVIOUS_KEYS'];

    private const SECRET = '/(_PASSWORD$|_PASS$|SECRET|TOKEN|_KEYS?$)/';

    private const PUBLIC_KEYS = ['TURNSTILE_SITE_KEY'];

    private const ORDER = [
        'application', 'branding', 'database', 'mail', 'accounts', 'login', 'profile', 'notifications', 'dashboard',
        'setup', 'panel', 'logs', 'infrastructure', 'other',
    ];

    /** Prefixo do nome => grupo. O mais específico vem antes. */
    private const GROUPS = [
        'APP_LOGO' => 'branding',
        'APP_FAVICON' => 'branding',
        'APP_PRIMARY' => 'branding',
        'APP_SECONDARY' => 'branding',
        'APP_' => 'application',
        'SERVER_' => 'application',
        'BCRYPT_' => 'application',
        'DB_' => 'database',
        'MAIL_' => 'mail',
        'REGISTRATION_' => 'accounts',
        'TEMPORARY_' => 'accounts',
        'PASSWORD_' => 'accounts',
        'LOGIN_' => 'login',
        'TURNSTILE_' => 'login',
        'PROFILE_' => 'profile',
        'ADDRESS_' => 'profile',
        'NOTIFICATIONS_' => 'notifications',
        'DASHBOARD_' => 'dashboard',
        'INITIAL_' => 'setup',
        'INSTALL_' => 'setup',
        'ENVIRONMENT_' => 'setup',
        'ADMIN_' => 'panel',
        'DEFAULT_PROFILE_' => 'panel',
        'EXPORT_' => 'panel',
        'LOG_' => 'logs',
        'HISTORY_' => 'logs',
        'SESSION_' => 'infrastructure',
        'CACHE_' => 'infrastructure',
        'QUEUE_' => 'infrastructure',
        'BROADCAST_' => 'infrastructure',
        'FILESYSTEM_' => 'infrastructure',
    ];

    public function __construct(private EnvFile $file) {}

    /**
     * @return array<int, array{key: string, label: string, parameters: array<int, array<string, mixed>>}>
     */
    public function groups(): array
    {
        $grouped = [];

        foreach ($this->parameters() as $parameter) {
            $grouped[$parameter['group']][] = $parameter;
        }

        return collect(self::ORDER)
            ->filter(fn (string $group) => isset($grouped[$group]))
            ->map(fn (string $group) => [
                'key' => $group,
                'label' => trans("environment.groups.{$group}", [], 'pt_BR'),
                'parameters' => $grouped[$group],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function secretKeys(): array
    {
        return collect($this->parameters())->where('secret', true)->pluck('key')->all();
    }

    /**
     * Confere o que veio da tela e devolve só o que mudou.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     *
     * @throws ValidationException
     */
    public function changes(array $input): array
    {
        $parameters = collect($this->parameters())->keyBy('key');
        $changes = [];
        $errors = [];

        foreach ($input as $key => $value) {
            $parameter = $parameters->get($key);
            $value = is_bool($value) ? ($value ? 'true' : 'false') : ($value ?? '');
            $error = $this->refusal($key, $value, $parameter);

            if ($error !== null) {
                $errors["values.{$key}"] = $error;

                continue;
            }

            $value = (string) $value;

            if ($value === ($parameter['value'] ?? '')) {
                continue;
            }

            $changes[$key] = $value;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $changes;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parameters(): array
    {
        $example = is_file(base_path('.env.example')) ? Dotenv::parse((string) file_get_contents(base_path('.env.example'))) : [];
        $current = $this->file->values();

        return collect([...array_keys($example), ...array_keys($current)])
            ->unique()
            ->values()
            ->map(function (string $key) use ($example, $current) {
                $documented = array_key_exists($key, $example);
                $secret = ! $documented || $this->isSecret($key);
                $value = $current[$key] ?? (string) ($example[$key] ?? '');
                $system = $_SERVER[$key] ?? $_ENV[$key] ?? null;
                $overridden = is_string($system) && array_key_exists($key, $current) && $system !== $value;

                return [
                    'key' => $key,
                    'group' => $this->group($key, $documented),
                    'description' => $this->description($key),
                    'type' => $this->type((string) ($example[$key] ?? '')),
                    'secret' => $secret,
                    'locked' => in_array($key, self::LOCKED, true),
                    'value' => $secret ? null : $value,
                    'has_value' => ($current[$key] ?? '') !== '',
                    'overridden' => $overridden,
                    'effective' => $overridden && ! $secret ? $system : null,
                ];
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>|null  $parameter
     */
    private function refusal(string $key, mixed $value, ?array $parameter): ?string
    {
        if ($parameter === null) {
            return __('This parameter does not exist.');
        }

        if ($parameter['locked']) {
            return __('This parameter cannot be changed here.');
        }

        if (! is_scalar($value) || preg_match('/[\x00-\x1F\x7F]/', (string) $value) === 1) {
            return __('Enter a single line of text.');
        }

        $value = (string) $value;

        if ($parameter['secret'] && $value === '') {
            return null;
        }

        return match (true) {
            $parameter['type'] === 'boolean' && ! in_array($value, ['true', 'false'], true) => __('Choose yes or no.'),
            $parameter['type'] === 'integer' && preg_match('/^\d+$/', $value) !== 1 => __('Enter a whole number.'),
            str_ends_with($key, '_PORT') && ((int) $value < 1 || (int) $value > 65535) => __('Enter a port from 1 to 65535.'),
            $key === 'APP_URL' && filter_var($value, FILTER_VALIDATE_URL) === false => __('Enter a valid address.'),
            default => null,
        };
    }

    private function isSecret(string $key): bool
    {
        return ! in_array($key, self::PUBLIC_KEYS, true) && preg_match(self::SECRET, $key) === 1;
    }

    private function group(string $key, bool $documented): string
    {
        if (! $documented) {
            return 'other';
        }

        foreach (self::GROUPS as $prefix => $group) {
            if (str_starts_with($key, $prefix)) {
                return $group;
            }
        }

        return 'other';
    }

    private function description(string $key): string
    {
        $path = "environment.descriptions.{$key}";
        $text = trans($path, [], 'pt_BR');

        return $text === $path ? trans('environment.custom', [], 'pt_BR') : $text;
    }

    private function type(string $example): string
    {
        return match (true) {
            in_array($example, ['true', 'false'], true) => 'boolean',
            preg_match('/^\d+$/', $example) === 1 => 'integer',
            default => 'text',
        };
    }
}
