import { PACKAGE_VENDOR, isOutsideShell, pageKey, resolvePage } from '@/pages';
import { describe, expect, test } from 'vitest';

const component = (name) => ({ default: () => name });

const globs = {
    pages: {
        './Pages/Admin/Users/Index.jsx': async () => component('users'),
        './Pages/Auth/Login.jsx': async () => component('login'),
    },
    packagePages: {
        [`../../vendor/${PACKAGE_VENDOR}/fake/resources/js/Pages/Admin/Index.jsx`]:
            async () => component('fake'),
        [`../../vendor/${PACKAGE_VENDOR}/fake/resources/js/Pages/Auth/Login.jsx`]:
            async () => component('fake-login'),
    },
};

describe('páginas do Template e dos pacotes', () => {
    test('nome sem pacote usa as páginas do Template', async () => {
        const page = await resolvePage('Admin/Users/Index', { globs });

        expect(page.default()).toBe('users');
        expect(pageKey('Admin/Users/Index')).toBe(
            './Pages/Admin/Users/Index.jsx',
        );
    });

    test('pacote::Caminho usa as páginas do pacote', async () => {
        const page = await resolvePage('fake::Admin/Index', { globs });

        expect(page.default()).toBe('fake');
        expect(pageKey('fake::Admin/Index')).toBe(
            '../../vendor/raphaelcosta27/fake/resources/js/Pages/Admin/Index.jsx',
        );
    });

    test('página inexistente falha do mesmo jeito, com ou sem pacote', async () => {
        await expect(resolvePage('Admin/Nada', { globs })).rejects.toThrow(
            'Page not found',
        );
        await expect(
            resolvePage('fake::Admin/Nada', { globs }),
        ).rejects.toThrow('Page not found');
        await expect(
            resolvePage('outro::Admin/Index', { globs }),
        ).rejects.toThrow('Page not found');
    });

    test('a regra de fora do layout vale para o caminho depois de ::', () => {
        expect(isOutsideShell('Auth/Login')).toBe(true);
        expect(isOutsideShell('Welcome')).toBe(true);
        expect(isOutsideShell('fake::Auth/Login')).toBe(true);
        expect(isOutsideShell('fake::Admin/Index')).toBe(false);
        expect(isOutsideShell('Admin/Users/Index')).toBe(false);
    });

    test('a página do pacote recebe o layout, e a de acesso não', async () => {
        const shell = (page) => page;

        const inside = await resolvePage('fake::Admin/Index', {
            globs,
            layout: shell,
        });
        const outside = await resolvePage('fake::Auth/Login', {
            globs,
            layout: shell,
        });

        expect(inside.default.layout).toBe(shell);
        expect(outside.default.layout).toBeUndefined();
    });
});

describe('build com pacotes linkados', () => {
    test('o Vite mantém o caminho do link em vendor', async () => {
        const { readFileSync } = await import('node:fs');
        const config = readFileSync('vite.config.js', 'utf8');

        // Com o Composer em modo path e symlink, o pacote fica fora do projeto.
        // Sem isto, os imports dele não acham o node_modules do Template.
        expect(config).toMatch(/preserveSymlinks:\s*true/);
    });
});
