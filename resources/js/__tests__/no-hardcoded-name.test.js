import { readdirSync, readFileSync } from 'node:fs';
import { join } from 'node:path';
import { expect, test } from 'vitest';

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

test('o logo vem somente da configuração', () => {
    const [, content] = sources().find(([file]) =>
        file.endsWith('ApplicationLogo.jsx'),
    );

    expect(content).toContain('branding.logo');
    expect(content).not.toContain('<svg');
});
