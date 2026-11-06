import ApplicationLogo from '@/Components/ApplicationLogo';
import ConfirmDialog from '@/Components/ConfirmDialog';
import Toolbar from '@/Components/DataTable/Toolbar';
import StatusAnnouncer from '@/Components/StatusAnnouncer';
import AppShell from '@/Layouts/AppShell';
import GuestLayout from '@/Layouts/GuestLayout';
import { fieldProps } from '@/lib/form';
import { createAppTheme } from '@/theme';
import { ThemeProvider } from '@mui/material/styles';
import { fireEvent, render, screen, within } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test, vi } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';

const form = { data: { email: '' }, errors: {}, setData: vi.fn() };

describe('área de marca', () => {
    beforeEach(() =>
        setPageProps({
            translations,
            appName: 'Minha Empresa',
            branding: { logo: '/claro.svg', tagline: 'Tudo em um lugar' },
        }),
    );

    test('mostra nome e frase fora do formulário', async () => {
        const { container } = render(<GuestLayout>formulário</GuestLayout>);
        const area = screen.getByRole('complementary', { name: 'Minha Empresa' });

        expect(within(area).getByText('Tudo em um lugar')).toBeTruthy();
        expect(screen.getByRole('main').textContent).toBe('formulário');
        expect(await axe(container)).toHaveNoViolations();
    });

    test('sem frase, nada sobra na área', () => {
        setPageProps({ branding: { logo: '/claro.svg', tagline: null } });
        render(<GuestLayout>formulário</GuestLayout>);

        expect(screen.queryByText('Tudo em um lugar')).toBeNull();
    });
});

describe('logo do tema', () => {
    const logo = (mode) =>
        render(
            <ThemeProvider theme={createAppTheme()} defaultMode={mode}>
                <ApplicationLogo />
            </ThemeProvider>,
        );

    beforeEach(() => {
        window.localStorage.clear();
        setPageProps({
            appName: 'Marca',
            branding: { logo: '/claro.svg', logoDark: '/escuro.svg' },
        });
    });

    test('o tema escuro usa a logo escura', () => {
        logo('dark');

        expect(screen.getByRole('img').getAttribute('src')).toBe('/escuro.svg');
    });

    test('o tema claro usa a logo principal', () => {
        logo('light');

        expect(screen.getByRole('img').getAttribute('src')).toBe('/claro.svg');
    });

    test('sem logo escura, usa a principal', () => {
        setPageProps({ branding: { logo: '/claro.svg' } });
        logo('dark');

        expect(screen.getByRole('img').getAttribute('src')).toBe('/claro.svg');
    });
});

describe('aviso flutuante', () => {
    test('aparece fora do fluxo e continua anunciado', () => {
        setPageProps({ flash: { type: 'success', message: 'Salvo' } });
        render(<StatusAnnouncer />);

        expect(screen.getByRole('status').textContent).toBe('Salvo');
        expect(document.querySelector('.MuiSnackbar-root').textContent).toBe('Salvo');
        expect(
            screen.getByRole('status').querySelector('.MuiAlert-root'),
        ).toBeNull();
    });

    test('a região oculta não abre aviso', () => {
        render(<StatusAnnouncer message="Força média" visuallyHidden />);

        expect(document.querySelector('.MuiSnackbar-root')).toBeNull();
        expect(screen.getByRole('status').textContent).toBe('Força média');
    });

    test('erro só sai quando a pessoa fecha', () => {
        vi.useFakeTimers();
        setPageProps({ errors: { user: 'Recusado' } });
        render(<StatusAnnouncer />);

        vi.advanceTimersByTime(60000);
        vi.useRealTimers();

        expect(document.querySelector('.MuiSnackbar-root').textContent).toBe('Recusado');
    });
});

describe('atalhos', () => {
    beforeEach(() => {
        window.localStorage.clear();
        setPageProps({
            translations,
            auth: {
                user: { name: 'Ana', email: 'a@b.c' },
                displayName: 'Ana',
                isAdministrator: true,
                permissions: [],
            },
        });
    });

    test('Ctrl+B recolhe e abre o menu', () => {
        render(<AppShell>conteúdo</AppShell>);

        fireEvent.keyDown(window, { key: 'b', ctrlKey: true });
        expect(screen.getByRole('button', { name: 'Expandir menu' })).toBeTruthy();

        fireEvent.keyDown(window, { key: 'b', ctrlKey: true });
        expect(screen.getByRole('button', { name: 'Recolher menu' })).toBeTruthy();
    });

    test('a letra sozinha não dispara o atalho', () => {
        render(<AppShell>conteúdo</AppShell>);

        fireEvent.keyDown(window, { key: 'b' });

        expect(screen.getByRole('button', { name: 'Recolher menu' })).toBeTruthy();
    });

    test('Ctrl+K leva à busca da tabela', () => {
        render(<Toolbar filters={[]} query={{}} onSearch={() => {}} />);

        fireEvent.keyDown(window, { key: 'k', ctrlKey: true });

        expect(document.activeElement).toBe(screen.getByRole('searchbox'));
    });
});

describe('campos', () => {
    test('o tipo define ícone e teclado', () => {
        const props = fieldProps(form, 'email', { kind: 'email' });

        expect(props.type).toBe('email');
        expect(props.slotProps.htmlInput.inputMode).toBe('email');
        expect(props.slotProps.input.startAdornment).toBeTruthy();
    });

    test('um ícone próprio vale sobre o do tipo', () => {
        const props = fieldProps(form, 'email', { kind: 'email', adornment: 'próprio' });

        expect(props.slotProps.input.startAdornment).toBe('próprio');
    });
});

describe('confirmação', () => {
    test('a ação destrutiva mantém o texto e ganha ícone', () => {
        setPageProps({ translations });
        render(
            <ConfirmDialog
                show
                title="Excluir"
                confirmLabel="Excluir registro"
                danger
                onConfirm={() => {}}
                onClose={() => {}}
            />,
        );

        const button = screen.getByRole('button', { name: 'Excluir registro' });

        expect(button.querySelector('svg')).toBeTruthy();
    });
});
