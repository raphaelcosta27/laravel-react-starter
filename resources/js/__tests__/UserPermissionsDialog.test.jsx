import UserPermissionsDialog from '@/Components/UserPermissionsDialog';
import { fireEvent, render, screen, within } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { router, setPageProps } from './helpers/inertia';

const permission = (id, title, values = {}) => ({
    id,
    title,
    description: `Sobre ${title}`,
    inProfile: false,
    assigned: false,
    available: true,
    ...values,
});

const modules = [
    {
        key: 'users',
        label: 'Usuários',
        view: 1,
        notice: null,
        permissions: [
            permission(1, 'Visualizar', { inProfile: true, assigned: true }),
            permission(2, 'Editar', { inProfile: true, assigned: true }),
            permission(3, 'Excluir'),
            permission(4, 'Exportar', { assigned: true }),
            permission(5, 'Restaurar', { inProfile: true }),
        ],
    },
];

const form = (values = {}) => ({
    mode: 'user-permissions',
    user: { id: 9, name: 'Bia' },
    canAdjust: true,
    origin: 'users',
    modules,
    ...values,
});

const dialog = (values) => {
    const view = render(<UserPermissionsDialog form={form(values)} />);

    return view;
};

const expand = () => fireEvent.click(screen.getByRole('button', { name: /Usuários/ }));

const row = (name) => screen.getByRole('checkbox', { name }).closest('label').parentElement.parentElement;

describe('modal de permissões do usuário', () => {
    beforeEach(() => {
        router.put.mockClear();
        setPageProps({ translations, auth: { user: { id: 1 } } });
    });

    test('abre com o estado guardado', () => {
        dialog();
        expand();

        expect(screen.getByRole('dialog', { name: 'Permissões de Bia' })).toBeTruthy();
        expect(screen.getByRole('checkbox', { name: 'Editar' }).checked).toBe(true);
        expect(screen.getByRole('checkbox', { name: 'Excluir' }).checked).toBe(false);
        expect(screen.getByRole('checkbox', { name: 'Exportar' }).checked).toBe(true);
        expect(screen.getByRole('checkbox', { name: 'Restaurar' }).checked).toBe(false);
    });

    test('o badge mostra a origem, com texto', () => {
        dialog();
        expand();

        expect(within(row('Editar')).getByText('Do perfil')).toBeTruthy();
        expect(within(row('Exportar')).getByText('Individual')).toBeTruthy();
        expect(within(row('Restaurar')).getByText('Revogada')).toBeTruthy();
        expect(within(row('Excluir')).queryByText(/Do perfil|Individual|Revogada/)).toBeNull();
    });

    test('o badge acompanha a marcação', () => {
        dialog();
        expand();

        fireEvent.click(screen.getByRole('checkbox', { name: 'Excluir' }));
        fireEvent.click(screen.getByRole('checkbox', { name: 'Editar' }));

        expect(within(row('Excluir')).getByText('Individual')).toBeTruthy();
        expect(within(row('Editar')).getByText('Revogada')).toBeTruthy();
    });

    test('salva só a diferença em relação ao perfil', () => {
        dialog();
        expand();

        fireEvent.click(screen.getByRole('checkbox', { name: 'Excluir' }));
        fireEvent.click(screen.getByRole('checkbox', { name: 'Editar' }));
        fireEvent.click(screen.getByRole('checkbox', { name: 'Restaurar' }));
        fireEvent.click(screen.getByRole('button', { name: 'Salvar' }));

        const [url, data] = router.put.mock.calls[0];

        expect(url).toBe('/admin.users.permissions.update');
        expect(data.origin).toBe('users');
        expect(data.adjustments).toEqual([
            { permission_id: 2, effect: 'revoke' },
            { permission_id: 3, effect: 'grant' },
            { permission_id: 4, effect: 'grant' },
        ]);
    });

    test('voltar ao estado do perfil desfaz o ajuste', () => {
        dialog();
        expand();

        fireEvent.click(screen.getByRole('checkbox', { name: 'Exportar' }));
        fireEvent.click(screen.getByRole('checkbox', { name: 'Restaurar' }));
        fireEvent.click(screen.getByRole('button', { name: 'Salvar' }));

        expect(router.put.mock.calls[0][1].adjustments).toEqual([]);
    });

    test('sem Visualizar, avisa que o restante não tem efeito', () => {
        dialog();
        expand();

        const warning = 'Sem Visualizar, as demais permissões deste módulo não têm efeito.';

        expect(screen.queryByText(warning)).toBeNull();

        fireEvent.click(screen.getByRole('checkbox', { name: 'Visualizar' }));

        expect(screen.getByText(warning)).toBeTruthy();
    });

    test('o módulo sem Visualizar não mostra o aviso', () => {
        dialog({
            modules: [{ ...modules[0], view: null, permissions: [permission(1, 'Histórico', { assigned: true })] }],
        });
        expand();

        expect(screen.queryByRole('alert')).toBeNull();
    });

    test('fechar com alterações pede confirmação', async () => {
        dialog();
        expand();

        fireEvent.click(screen.getByRole('checkbox', { name: 'Excluir' }));
        fireEvent.click(screen.getByRole('button', { name: 'Cancelar' }));

        expect(await screen.findByText('Descartar alterações')).toBeTruthy();
    });

    test('administrador é informado de que tem tudo', () => {
        dialog({ canAdjust: false });

        expect(screen.getByText('Este perfil tem acesso total ao sistema.')).toBeTruthy();
        expect(screen.queryByRole('checkbox')).toBeNull();
        expect(screen.queryByRole('button', { name: 'Salvar' })).toBeNull();
    });

    test('não tem violações', async () => {
        const { baseElement } = dialog();

        expand();

        expect(await axe(baseElement)).toHaveNoViolations();
    });
});
