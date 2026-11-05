import { readdirSync, readFileSync } from 'node:fs';
import { join, relative } from 'node:path';
import { describe, expect, test } from 'vitest';

const ROOT = join(process.cwd(), 'resources/js');

const LEGACY = /className=|@headlessui/;

const sources = (directory) =>
    readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
        const path = join(directory, entry.name);

        if (entry.isDirectory()) {
            return entry.name === '__tests__' ? [] : sources(path);
        }

        return /\.jsx?$/.test(entry.name) ? [path] : [];
    });

const legacy = sources(ROOT)
    .filter((path) => LEGACY.test(readFileSync(path, 'utf8')))
    .map((path) => relative(ROOT, path).replaceAll('\\', '/'))
    .sort();

describe('estilos anteriores', () => {
    test('nenhum arquivo usa Tailwind ou Headless UI', () => {
        expect(legacy).toEqual([]);
    });

    test('a aplicação não carrega fonte de serviço externo', () => {
        const view = readFileSync(
            join(process.cwd(), 'resources/views/app.blade.php'),
            'utf8',
        );

        expect(view).not.toMatch(/<link[^>]+https?:/);
    });
});
