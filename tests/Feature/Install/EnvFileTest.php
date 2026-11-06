<?php

namespace Tests\Feature\Install;

use App\Support\Environment\EnvFile;
use App\Support\Environment\EnvFileConflict;
use Dotenv\Dotenv;
use PHPUnit\Framework\Attributes\DataProvider;

class EnvFileTest extends EnvironmentTestCase
{
    private function file(): EnvFile
    {
        return app(EnvFile::class);
    }

    public function test_only_the_changed_line_is_touched(): void
    {
        $this->env("# Comentário\nAPP_NAME=Antigo # nota\n\nDESCONHECIDA=1\nexport OUTRA=2\n");

        $this->file()->save(['APP_NAME' => 'Novo']);

        $this->assertSame("# Comentário\nAPP_NAME=Novo\n\nDESCONHECIDA=1\nexport OUTRA=2\n", $this->envContents());
    }

    public function test_windows_line_endings_are_kept(): void
    {
        $this->env("A=1\r\nB=2\r\n");

        $this->file()->save(['A' => '3']);

        $this->assertSame("A=3\r\nB=2\r\n", $this->envContents());
    }

    public function test_a_new_key_goes_to_the_end(): void
    {
        $this->env('A=1');

        $this->file()->save(['B' => '2']);

        $this->assertSame(['A' => '1', 'B' => '2'], $this->file()->values());
    }

    public function test_a_key_is_not_confused_with_a_longer_one(): void
    {
        $this->env("DB_HOST_READ=1\nDB_HOST=2\n");

        $this->file()->save(['DB_HOST' => '3']);

        $this->assertSame(['DB_HOST_READ' => '1', 'DB_HOST' => '3'], $this->file()->values());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function hardValues(): array
    {
        return [
            'espaços' => ['Meu Sistema'],
            'aspas' => ['diz "oi" e \'tchau\''],
            'cerquilha' => ['a#b #c'],
            'cifrão' => ['pa$$word ${APP_NAME}'],
            'barra invertida' => ['C:\\pasta\\novo'],
            'igual' => ['a=b='],
            'acentos' => ['Educação'],
            'vazio' => [''],
            'ponto e vírgula' => [';'],
            'termina em barra' => ['fim\\'],
        ];
    }

    #[DataProvider('hardValues')]
    public function test_hard_values_stay_valid(string $value): void
    {
        $this->env("VALOR=1\nDEPOIS=2\n");

        $this->file()->save(['VALOR' => $value]);

        $parsed = Dotenv::parse($this->envContents());

        $this->assertSame($value, (string) $parsed['VALOR']);
        $this->assertSame('2', $parsed['DEPOIS']);
    }

    public function test_a_previous_copy_is_kept_outside_the_public_area(): void
    {
        $this->env("A=1\n");

        $this->file()->save(['A' => '2']);

        $this->assertSame("A=1\n", file_get_contents(config('template.install.directory').'/.env.backup'));
    }

    public function test_a_stale_version_is_refused_and_nothing_changes(): void
    {
        $this->env("A=1\n");
        $version = $this->file()->version();

        $this->file()->save(['A' => '2']);

        try {
            $this->file()->save(['A' => '3'], $version);
            $this->fail('A versão antiga deveria ser recusada.');
        } catch (EnvFileConflict) {
            $this->assertSame("A=2\n", $this->envContents());
        }
    }

    public function test_no_temporary_file_is_left_behind(): void
    {
        $this->env("A=1\n");

        $this->file()->save(['A' => '2']);

        $this->assertFileDoesNotExist($this->envPath().'.tmp');
    }
}
