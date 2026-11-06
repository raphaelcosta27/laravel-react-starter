<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DocsTest extends TestCase
{
    private const SECTIONS = ['## Objetivo', '## Regras', '## Comportamentos', '## Configuração', '## Decisões'];

    private const VARIABLE = '/`((?:APP|DB|MAIL|LOGIN|PROFILE|ADDRESS|NOTIFICATIONS|LOG|HISTORY|INSTALL|ENVIRONMENT|TURNSTILE|PASSWORD|REGISTRATION|TEMPORARY|ADMIN|DEFAULT|EXPORT|INITIAL|DOCKER|DASHBOARD)_[A-Z_]+)`/';

    private const FORBIDDEN = [
        'caminho local do Windows' => '/\b[A-Z]:\\\\/',
        'pasta de usuário' => '~/(?:Users|home)/\w+~',
        'tarefa interna' => '/\bT\d{3}\b/',
        'chave' => '/base64:[A-Za-z0-9+\/]{20,}/',
        'e-mail real' => '/[\w.+-]+@(?!example\.)[\w-]+\.[a-z]{2,}/i',
    ];

    /**
     * @return array<int, string>
     */
    private function documents(): array
    {
        return [
            base_path('README.md'),
            base_path('README.pt-BR.md'),
            base_path('CONTRIBUTING.md'),
            base_path('docker/README.md'),
            ...$this->publicDocuments(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function publicDocuments(): array
    {
        return array_map(fn ($file) => $file->getPathname(), File::allFiles(base_path('docs')));
    }

    /**
     * @return array<int, string>
     */
    private function specifications(): array
    {
        return array_map(fn ($file) => $file->getPathname(), File::files(base_path('docs/especificacoes')));
    }

    public function test_internal_links_are_not_broken(): void
    {
        $broken = [];

        foreach ($this->documents() as $document) {
            preg_match_all('/\]\((?!https?:|mailto:|#)([^)#\s]+)/', (string) file_get_contents($document), $links);

            foreach ($links[1] as $link) {
                if (! file_exists(dirname($document).DIRECTORY_SEPARATOR.$link)) {
                    $broken[] = basename($document)." -> {$link}";
                }
            }
        }

        $this->assertSame([], $broken);
    }

    public function test_every_specification_is_in_the_index(): void
    {
        $index = (string) file_get_contents(base_path('docs/README.md'));

        foreach ($this->specifications() as $file) {
            $this->assertStringContainsString('especificacoes/'.basename($file), $index);
        }
    }

    public function test_every_delivered_feature_has_a_specification(): void
    {
        $this->assertGreaterThanOrEqual(15, count($this->specifications()));
    }

    public function test_every_specification_has_the_expected_sections(): void
    {
        foreach ($this->specifications() as $file) {
            foreach (self::SECTIONS as $section) {
                $this->assertStringContainsString($section, (string) file_get_contents($file), basename($file));
            }
        }
    }

    public function test_mentioned_variables_exist(): void
    {
        $known = file_get_contents(base_path('.env.example')).file_get_contents(base_path('docker/.env.example'));

        foreach ($this->publicDocuments() as $document) {
            preg_match_all(self::VARIABLE, (string) file_get_contents($document), $variables);

            foreach ($variables[1] as $variable) {
                $this->assertStringContainsString("{$variable}=", $known, basename($document));
            }
        }
    }

    public function test_documents_carry_nothing_private(): void
    {
        foreach ([...$this->publicDocuments(), base_path('CONTRIBUTING.md'), base_path('docker/README.md')] as $document) {
            foreach (self::FORBIDDEN as $name => $pattern) {
                $this->assertSame(0, preg_match($pattern, (string) file_get_contents($document)), "{$name} em ".basename($document));
            }
        }
    }

    public function test_the_readme_points_to_the_documentation(): void
    {
        foreach (['README.md', 'README.pt-BR.md'] as $readme) {
            $this->assertStringContainsString('docs/README.md', (string) file_get_contents(base_path($readme)));
        }
    }

    public function test_working_specifications_stay_out_of_git(): void
    {
        $ignored = (string) file_get_contents(base_path('.gitignore'));

        $this->assertMatchesRegularExpression('~^/?specs/?$~m', $ignored);
        $this->assertMatchesRegularExpression('~^/?\.specify/?~m', $ignored);
    }
}
