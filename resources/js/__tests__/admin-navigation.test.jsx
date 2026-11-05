import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { render, screen, within } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';

const areas = ['Perfis', 'Permissões', 'Usuários'];

const layout = (auth) => {
    setPageProps({
        translations,
        auth: { user: { name: 'Ana', email: 'ana@example.com' }, ...auth },
    });

    return render(<AuthenticatedLayout>conteúdo</AuthenticatedLayout>);
};

const menu = () => within(screen.getByRole('navigation'));

/** O menu lateral fechado fica invisível e sem nome acessível: conta pelo texto. */
const links = (area) =>
    menu()
        .getAllByRole('link', { hidden: true })
        .filter((link) => link.textContent === area);

describe('menu do painel', () => {
    beforeEach(() => setPageProps({ translations }));

    test('administrador vê as três áreas nos dois menus', () => {
        layout({ isAdministrator: true, permissions: [] });

        areas.forEach((area) => {
            expect(links(area)).toHaveLength(2);
        });
    });

    test('usuário vê somente as áreas permitidas', () => {
        layout({ isAdministrator: false, permissions: ['visualizar_usuario'] });

        expect(links('Usuários')).toHaveLength(2);
        expect(links('Perfis')).toHaveLength(0);
        expect(links('Permissões')).toHaveLength(0);
    });

    test('usuário sem permissões não vê o painel', () => {
        layout({ isAdministrator: false, permissions: [] });

        areas.forEach((area) => {
            expect(links(area)).toHaveLength(0);
        });
    });

    test('exibe a mensagem de flash em região anunciada', () => {
        setPageProps({ flash: { type: 'success', message: 'Perfil salvo' } });
        layout({ isAdministrator: true, permissions: [] });

        expect(screen.getByRole('status').textContent).toBe('Perfil salvo');
    });

    test('não tem violações de acessibilidade', async () => {
        const { container } = layout({ isAdministrator: true, permissions: [] });

        expect(await axe(container)).toHaveNoViolations();
    });
});
