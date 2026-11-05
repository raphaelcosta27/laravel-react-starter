import { readdirSync, readFileSync } from 'node:fs';
import { join } from 'node:path';
import { expect, test } from 'vitest';
import translations from '../../../lang/pt_BR.json';

const root = join(process.cwd(), 'resources');

const sources = () =>
    readdirSync(root, { recursive: true })
        .filter((file) => /\.(jsx?|blade\.php)$/.test(file))
        .filter((file) => !file.includes('__tests__'))
        .map((file) => [file, readFileSync(join(root, file), 'utf8')]);

test('nenhum arquivo traz nome ou marca fixos', () => {
    const offenders = sources()
        .filter(([, content]) => /\bLaravel\b|VITE_APP_NAME/.test(content))
        .map(([file]) => file);

    expect(offenders).toEqual([]);
});

const panelSources = () =>
    sources().filter(([file]) =>
        /Pages[\\/]Admin|Components[\\/]DataTable|ConfirmDialog/.test(file),
    );

test('as telas do painel não trazem texto fixo no JSX', () => {
    const offenders = panelSources()
        .filter(([file]) => file.endsWith('.jsx'))
        .filter(([, content]) =>
            />\s*[A-Za-zÀ-ÿ][A-Za-zÀ-ÿ ]{2,}\s*</.test(content),
        )
        .map(([file]) => file);

    expect(offenders).toEqual([]);
});

test('todo texto do painel tem tradução', () => {
    const missing = panelSources().flatMap(([, content]) =>
        [...content.matchAll(/__\(\s*'((?:[^'\\]|\\.)+)'/g)]
            .map(([, key]) => key.replaceAll("\\'", "'"))
            .filter((key) => !(key in translations)),
    );

    expect(missing).toEqual([]);
});

test('o logo vem somente da configuração', () => {
    const [, content] = sources().find(([file]) =>
        file.endsWith('ApplicationLogo.jsx'),
    );

    expect(content).toContain('branding.logo');
    expect(content).not.toContain('<svg');
});
