import ThemeSwitcher from '@/Components/Shell/ThemeSwitcher';
import { createAppTheme } from '@/theme';
import MenuList from '@mui/material/MenuList';
import { ThemeProvider } from '@mui/material/styles';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { axe } from 'jest-axe';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';

const answer = (ok = true) =>
    vi.fn().mockResolvedValue({
        ok,
        status: ok ? 200 : 500,
        json: () => Promise.resolve({}),
    });

const switcher = () =>
    render(
        <ThemeProvider theme={createAppTheme()} defaultMode="system">
            <MenuList>
                <ThemeSwitcher />
            </MenuList>
        </ThemeProvider>,
    );

const option = (name) => screen.getByRole('menuitemradio', { name });

describe('ThemeSwitcher', () => {
    beforeEach(() => {
        window.localStorage.clear();
        setPageProps({ translations });
        globalThis.fetch = answer();
    });

    afterEach(() => {
        delete globalThis.fetch;
    });

    test('oferece claro, escuro e sistema, com o atual marcado', () => {
        switcher();

        expect(screen.getAllByRole('menuitemradio')).toHaveLength(3);
        expect(option('Sistema').getAttribute('aria-checked')).toBe('true');
        expect(option('Claro').getAttribute('aria-checked')).toBe('false');
        expect(option('Escuro').querySelector('svg')).toBeTruthy();
    });

    test('aplica na hora e guarda na conta', async () => {
        switcher();

        fireEvent.click(option('Escuro'));

        await waitFor(() =>
            expect(option('Escuro').getAttribute('aria-checked')).toBe('true'),
        );

        expect(window.localStorage.getItem('mui-mode')).toBe('dark');

        const [url, request] = globalThis.fetch.mock.calls[0];

        expect(url).toBe('/profile.theme.update');
        expect(request.method).toBe('PATCH');
        expect(JSON.parse(request.body)).toEqual({ theme: 'dark' });
    });

    test('falha ao guardar não interrompe a pessoa', async () => {
        globalThis.fetch = answer(false);
        switcher();

        fireEvent.click(option('Claro'));

        await waitFor(() =>
            expect(option('Claro').getAttribute('aria-checked')).toBe('true'),
        );

        expect(screen.queryByRole('alert')).toBeNull();
    });

    test('não tem violações de acessibilidade', async () => {
        const { container } = switcher();

        expect(await axe(container)).toHaveNoViolations();
    });
});
