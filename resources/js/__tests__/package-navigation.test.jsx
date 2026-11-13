import AppShell from '@/Layouts/AppShell';
import { navigationIcon } from '@/navigationIcons';
import ExtensionOutlinedIcon from '@mui/icons-material/ExtensionOutlined';
import { fireEvent, render, screen, within } from '@testing-library/react';
import { axe } from 'jest-axe';
import { afterEach, describe, expect, test } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';

const defaultRoute = globalThis.route;

const fakeItem = (overrides = {}) => ({
    key: 'fake_module',
    label: 'Fake module',
    route: 'admin.fake',
    index: 'admin.fake.index',
    icon: 'article',
    group: 'config',
    permission: 'visualizar_fake',
    ...overrides,
});

const layout = (auth, items = [fakeItem()]) => {
    setPageProps({
        translations,
        packageNavigation: items,
        auth: { user: { name: 'Ana', email: 'ana@example.com' }, ...auth },
    });

    const view = render(<AppShell>conteúdo</AppShell>);
    const config = screen.queryByRole('button', { name: 'Config' });

    if (config) {
        fireEvent.click(config);
    }

    return view;
};

const menu = () => within(screen.getByRole('navigation'));

const fake = () => menu().queryByRole('link', { name: 'Fake module' });

describe('menu dos pacotes', () => {
    afterEach(() => {
        globalThis.route = defaultRoute;
    });

    test('quem tem a permissão vê o item em Config, depois dos itens do Template', () => {
        layout({
            isAdministrator: false,
            permissions: ['visualizar_usuario', 'visualizar_fake'],
        });

        const links = menu()
            .getAllByRole('link')
            .map((link) => link.textContent);

        expect(fake()).not.toBeNull();
        expect(fake().getAttribute('href')).toBe('/admin.fake.index');
        expect(links.indexOf('Fake module')).toBeGreaterThan(
            links.indexOf('Usuários'),
        );
    });

    test('quem não tem a permissão não vê o item nem Config vazio', () => {
        layout({ isAdministrator: false, permissions: ['editar_fake'] });

        expect(fake()).toBeNull();
        expect(screen.queryByRole('button', { name: 'Config' })).toBeNull();
    });

    test('administrador vê o item', () => {
        layout({ isAdministrator: true, permissions: [] });

        expect(fake()).not.toBeNull();
    });

    test('item sem grupo fica no menu principal, fora de Config', () => {
        layout({ isAdministrator: false, permissions: ['visualizar_fake'] }, [
            fakeItem({ group: null }),
        ]);

        expect(fake()).not.toBeNull();
        expect(screen.queryByRole('button', { name: 'Config' })).toBeNull();
    });

    test('usa o ícone do mapa e o de extensão para nome desconhecido', () => {
        layout({ isAdministrator: true, permissions: [] }, [
            fakeItem({ icon: 'inexistente' }),
        ]);

        expect(
            fake().querySelector('[data-testid="ExtensionOutlinedIcon"]'),
        ).not.toBeNull();
        expect(navigationIcon('inexistente')).toBe(ExtensionOutlinedIcon);
        expect(navigationIcon('article')).not.toBe(ExtensionOutlinedIcon);
    });

    test('o item fica destacado nas rotas do pacote', () => {
        globalThis.route = (name) =>
            name === undefined
                ? { current: (pattern) => pattern === 'admin.fake.*' }
                : `/${name}`;

        layout({ isAdministrator: true, permissions: [] });

        expect(fake().getAttribute('aria-current')).toBe('page');
    });

    test('sem pacotes, o menu continua igual', () => {
        layout({ isAdministrator: true, permissions: [] }, null);

        expect(fake()).toBeNull();
        expect(menu().getAllByRole('link').length).toBeGreaterThan(0);
    });

    test('não tem violações de acessibilidade', async () => {
        const { container } = layout({
            isAdministrator: true,
            permissions: [],
        });

        expect(await axe(container)).toHaveNoViolations();
    });
});
