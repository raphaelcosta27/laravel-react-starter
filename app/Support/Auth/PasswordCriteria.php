<?php

namespace App\Support\Auth;

use Illuminate\Support\Arr;

/**
 * Definição única dos critérios de senha. O servidor valida com estes padrões
 * e o navegador os recebe para a barra de força, com as mesmas flags "su".
 */
class PasswordCriteria
{
    public const MINIMUM_LENGTH = 8;

    /** O bcrypt ignora o que passa de 72 bytes. */
    public const MAXIMUM_LENGTH = 72;

    private const GENERATED_LENGTH = 12;

    /**
     * Sem letras e números parecidos. Os símbolos não têm efeito no Markdown do
     * e-mail, que alteraria a senha enviada.
     */
    private const GENERATED_CHARACTERS = [
        'ABCDEFGHJKLMNPQRSTUVWXYZ',
        'abcdefghijkmnopqrstuvwxyz',
        '23456789',
        '!#$%+=?',
    ];

    /**
     * @return array<int, array{key: string, label: string, pattern: string}>
     */
    public static function all(): array
    {
        $length = self::minimumLength();

        return [
            ['key' => 'length', 'label' => __('At least :count characters', ['count' => $length]), 'pattern' => '.{'.$length.'}'],
            ['key' => 'uppercase', 'label' => __('One uppercase letter'), 'pattern' => '\p{Lu}'],
            ['key' => 'lowercase', 'label' => __('One lowercase letter'), 'pattern' => '\p{Ll}'],
            ['key' => 'number', 'label' => __('One number'), 'pattern' => '\p{N}'],
            ['key' => 'symbol', 'label' => __('One special character'), 'pattern' => '[\p{P}\p{S}\p{Z}]'],
        ];
    }

    /**
     * @param  array{pattern: string}  $criterion
     */
    public static function meets(array $criterion, string $password): bool
    {
        return preg_match('/'.$criterion['pattern'].'/su', $password) === 1;
    }

    /**
     * Sorteia um caractere de cada grupo, para cumprir todos os critérios.
     */
    public static function generate(): string
    {
        $pick = fn (string $characters) => $characters[random_int(0, strlen($characters) - 1)];

        $password = array_map($pick, self::GENERATED_CHARACTERS);
        $length = max(self::GENERATED_LENGTH, self::minimumLength());

        while (count($password) < $length) {
            $password[] = $pick(implode(self::GENERATED_CHARACTERS));
        }

        return implode(Arr::shuffle($password));
    }

    private static function minimumLength(): int
    {
        return min(self::MAXIMUM_LENGTH, max(self::MINIMUM_LENGTH, (int) config('template.password.min_length')));
    }
}
