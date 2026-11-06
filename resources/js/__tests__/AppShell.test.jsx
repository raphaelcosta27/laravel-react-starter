import AppShell from '@/Layouts/AppShell';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { createAppTheme } from '@/theme';
import { ThemeProvider } from '@mui/material/styles';
import { act, fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import { axe } from 'jest-axe';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';

const shell = (content = 'conteúdo') => (
    <AppShell>
        <AuthenticatedLayout title="Usuários">{content}</AuthenticatedLayout>
    </AppShell>
);

const nav = () => within(screen.getByRole('navigation'));

const openConfig = () =>
    fireEvent.click(nav().getByRole('button', { name: 'Config' }));

const account = (auth = {}) =>
    setPageProps({
        translations,
        auth: {
            user: { name: 'Ana', email: 'ana@example.com' },
            displayName: 'Ana',
            isAdministrator: true,
            permissions: [],
            ...auth,
        },
    });

const width = (pixels) => {
    window.matchMedia = vi.fn().mockImplementation((query) => ({
        matches: pixels <= Number(/max-width:\s*([\d.]+)/.exec(query)?.[1] ?? 0),
        media: query,
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
        addListener: vi.fn(),
        removeListener: vi.fn(),
    }));
};

describe('AppShell', () => {
    beforeEach(() => {
        window.localStorage.clear();
        account();
    });

    afterEach(() => {
        delete window.matchMedia;
        vi.restoreAllMocks();
        globalThis.route = (name) =>
            name === undefined ? { current: () => false } : `/${name}`;
    });

    test('mostra os módulos permitidos, com ícone antes do nome', () => {
        account({ isAdministrator: false, permissions: ['visualizar_usuario'] });
        render(shell());
        openConfig();

        const link = nav().getByRole('link', { name: 'Usuários' });

        expect(link.querySelector('svg')).toBeTruthy();
        expect(nav().getByRole('link', { name: 'Painel' })).toBeTruthy();
        expect(nav().queryByRole('link', { name: 'Perfis' })).toBeNull();
        expect(nav().queryByRole('link', { name: 'Permissões' })).toBeNull();
    });

    test('todo módulo do administrador aparece com ícone', () => {
        render(shell());
        openConfig();

        ['Perfis', 'Permissões', 'Usuários', 'Ambiente'].forEach((name) => {
            expect(
                nav().getByRole('link', { name }).querySelector('svg'),
            ).toBeTruthy();
        });
    });

    test('destaca o módulo também nas telas internas dele', () => {
        globalThis.route = (name) =>
            name === undefined
                ? { current: (pattern) => pattern === 'admin.users.*' }
                : `/${name}`;

        render(shell());

        expect(
            nav().getByRole('link', { name: 'Usuários' }).getAttribute('aria-current'),
        ).toBe('page');
        expect(
            nav().getByRole('link', { name: 'Perfis' }).getAttribute('aria-current'),
        ).toBeNull();
    });

    test('recolhe o menu, mantém o nome acessível e lembra a escolha', () => {
        const { unmount } = render(shell());

        expect(nav().getByText('Painel')).toBeTruthy();

        fireEvent.click(screen.getByRole('button', { name: 'Recolher menu' }));

        expect(nav().queryByText('Painel')).toBeNull();
        expect(nav().getByRole('link', { name: 'Painel' })).toBeTruthy();
        expect(window.localStorage.getItem('shell:collapsed')).toBe('true');

        unmount();
        render(shell());

        expect(screen.getByRole('button', { name: 'Expandir menu' })).toBeTruthy();
        expect(nav().queryByText('Painel')).toBeNull();
    });

    test('sem armazenamento, o menu continua funcionando', () => {
        vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
            throw new Error('bloqueado');
        });
        vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
            throw new Error('bloqueado');
        });

        render(shell());

        fireEvent.click(screen.getByRole('button', { name: 'Recolher menu' }));

        expect(screen.getByRole('button', { name: 'Expandir menu' })).toBeTruthy();
    });

    test('em largura de tablet, o menu fica recolhido e sem botão', () => {
        width(768);
        render(shell());

        expect(nav().queryByText('Painel')).toBeNull();
        expect(nav().getByRole('link', { name: 'Painel' })).toBeTruthy();
        expect(screen.queryByRole('button', { name: 'Recolher menu' })).toBeNull();
    });

    describe('Config', () => {
        const order = (first, second) =>
            first.compareDocumentPosition(second) & Node.DOCUMENT_POSITION_FOLLOWING;

        test('o Painel fica no topo, com ícone de casa, e os demais saem de lá', () => {
            render(shell());

            const panel = nav().getByRole('link', { name: 'Painel' });

            expect(panel.querySelector('[data-testid="HomeOutlinedIcon"]')).toBeTruthy();
            expect(nav().queryByRole('link', { name: 'Usuários' })).toBeNull();
        });

        test('fica logo acima da conta, com ícone de engrenagem', () => {
            render(shell());

            const config = nav().getByRole('button', { name: 'Config' });
            const account = nav().getByRole('button', { name: 'Olá, Ana' });

            expect(config.querySelector('[data-testid="SettingsOutlinedIcon"]')).toBeTruthy();
            expect(order(nav().getByRole('link', { name: 'Painel' }), config)).toBeTruthy();
            expect(order(config, account)).toBeTruthy();
            expect(config.closest('div').parentElement.parentElement).toBe(
                account.parentElement,
            );
        });

        test('expande e recolhe a lista, entre o botão e a conta', () => {
            render(shell());

            const config = nav().getByRole('button', { name: 'Config' });

            expect(config.getAttribute('aria-expanded')).toBe('false');

            openConfig();

            const names = within(nav().getByRole('list', { name: 'Config' }))
                .getAllByRole('link')
                .map((link) => link.textContent);

            expect(config.getAttribute('aria-expanded')).toBe('true');
            expect(names).toEqual([
                'Usuários',
                'Perfis',
                'Permissões',
                'Ambiente',
            ]);
            expect(
                order(
                    config,
                    nav().getByRole('link', { name: 'Usuários' }),
                ),
            ).toBeTruthy();
            expect(
                order(
                    nav().getByRole('link', { name: 'Ambiente' }),
                    nav().getByRole('button', { name: 'Olá, Ana' }),
                ),
            ).toBeTruthy();
        });

        test('quem não é administrador vê a seção com o módulo que tem', () => {
            account({ isAdministrator: false, permissions: ['visualizar_perfil'] });
            render(shell());
            openConfig();

            const names = within(nav().getByRole('list', { name: 'Config' }))
                .getAllByRole('link')
                .map((link) => link.textContent);

            expect(names).toEqual(['Perfis']);
        });

        test('sem nenhum módulo da seção, ela não aparece', () => {
            account({ isAdministrator: false, permissions: [] });
            render(shell());

            expect(nav().queryByRole('button', { name: 'Config' })).toBeNull();
            expect(nav().getByRole('link', { name: 'Painel' })).toBeTruthy();
        });

        test('já abre quando a tela atual é da seção', () => {
            globalThis.route = (name) =>
                name === undefined
                    ? { current: (pattern) => pattern === 'admin.profiles.*' }
                    : `/${name}`;

            render(shell());

            expect(
                nav().getByRole('button', { name: 'Config' }).getAttribute('aria-expanded'),
            ).toBe('true');
        });

        test('no menu recolhido, a seção funciona só com ícones', () => {
            window.localStorage.setItem('shell:collapsed', 'true');
            render(shell());
            openConfig();

            expect(nav().queryByText('Usuários')).toBeNull();
            expect(nav().getByRole('link', { name: 'Usuários' })).toBeTruthy();
        });
    });

    describe('no celular', () => {
        beforeEach(async () => {
            // O histórico do teste anterior termina de voltar antes de seguir.
            await new Promise((done) => setTimeout(done, 5));
            width(375);
        });

        const openMenu = () =>
            fireEvent.click(screen.getByRole('button', { name: 'Abrir menu' }));

        test('o menu fica totalmente oculto, com o ícone na barra', () => {
            render(shell());

            expect(screen.queryByRole('navigation')).toBeNull();
            expect(screen.queryByRole('link', { name: 'Usuários' })).toBeNull();
            expect(
                screen
                    .getByRole('button', { name: 'Abrir menu' })
                    .querySelector('svg[data-testid="MenuIcon"]'),
            ).toBeTruthy();
            expect(screen.queryByRole('button', { name: 'Recolher menu' })).toBeNull();
        });

        test('o ícone abre o menu completo pela lateral esquerda', async () => {
            render(shell());

            openMenu();

            const panel = await screen.findByRole('dialog', {
                name: 'Menu de navegação',
            });

            expect(panel.closest('.MuiDrawer-anchorLeft')).toBeTruthy();
            fireEvent.click(within(panel).getByRole('button', { name: 'Config' }));

            expect(within(panel).getByText('Painel')).toBeTruthy();
            expect(within(panel).getByText('Usuários')).toBeTruthy();
            expect(within(panel).getByText('Perfis')).toBeTruthy();
            expect(
                within(panel).getByRole('button', { name: 'Olá, Ana' }),
            ).toBeTruthy();
        });

        test('escolher um módulo fecha o menu', async () => {
            render(shell());

            openMenu();
            fireEvent.click(await screen.findByRole('button', { name: 'Config' }));
            fireEvent.click(await screen.findByRole('link', { name: 'Usuários' }));

            await waitFor(() =>
                expect(screen.queryByRole('dialog')).toBeNull(),
            );
        });

        test('o voltar do aparelho fecha o menu e mantém a tela', async () => {
            render(shell());

            openMenu();
            await screen.findByRole('dialog', { name: 'Menu de navegação' });

            act(() => {
                window.dispatchEvent(new PopStateEvent('popstate'));
            });

            await waitFor(() =>
                expect(screen.queryByRole('dialog')).toBeNull(),
            );
        });

        test('as notificações ficam na barra', () => {
            setPageProps({ notifications: { unread: 2, pollSeconds: 0 } });
            render(shell());

            expect(
                within(screen.getByRole('banner')).getByRole('button', {
                    name: /Notificações/,
                }),
            ).toBeTruthy();
        });

        test('a barra mostra o título, e o cabeçalho continua na tela', () => {
            render(shell());

            expect(
                screen.getByRole('heading', { level: 1, name: 'Usuários' }),
            ).toBeTruthy();
        });
    });

    test('o menu não desmonta ao trocar de tela', () => {
        const { rerender } = render(shell('primeira'));
        const menu = screen.getByRole('navigation');

        rerender(shell('segunda'));

        expect(screen.getByRole('navigation')).toBe(menu);
        expect(screen.getByRole('main').textContent).toContain('segunda');
    });

    test('a conta mostra o nome, o perfil, os temas e a saída', async () => {
        render(<ThemeProvider theme={createAppTheme()}>{shell()}</ThemeProvider>);

        fireEvent.click(screen.getByRole('button', { name: 'Olá, Ana' }));

        const menu = await screen.findByRole('menu');

        expect(within(menu).getByRole('menuitem', { name: 'Perfil' })).toBeTruthy();
        expect(within(menu).getByRole('menuitem', { name: 'Sair' })).toBeTruthy();
        expect(within(menu).getAllByRole('menuitemradio')).toHaveLength(3);
    });

    test('anuncia o aviso em região própria', () => {
        setPageProps({ flash: { type: 'success', message: 'Perfil salvo' } });
        render(shell());

        expect(screen.getByRole('status').textContent).toBe('Perfil salvo');
    });

    test('oferece o atalho para o conteúdo', () => {
        render(shell());

        expect(
            screen
                .getByRole('link', { name: 'Pular para o conteúdo' })
                .getAttribute('href'),
        ).toBe('#content');
        expect(screen.getByRole('main').id).toBe('content');
    });

    test.each([['light'], ['dark']])(
        'não tem violações de acessibilidade no tema %s',
        async (mode) => {
            const { container } = render(
                <ThemeProvider theme={createAppTheme()} defaultMode={mode}>
                    {shell()}
                </ThemeProvider>,
            );

            expect(await axe(container)).toHaveNoViolations();
        },
    );

    test('a escolha da conta vale sobre a do aparelho', () => {
        window.localStorage.setItem('mui-mode', 'light');
        account({ theme: 'dark' });

        render(<ThemeProvider theme={createAppTheme()}>{shell()}</ThemeProvider>);

        expect(window.localStorage.getItem('mui-mode')).toBe('dark');
    });
});
