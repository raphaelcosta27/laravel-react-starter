import Index from '@/Pages/Admin/Users/Index';
import { router } from '@inertiajs/react';
import { fireEvent, render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { expectDescribedActions } from './helpers/actions';
import { setPageProps } from './helpers/inertia';

const meta = { currentPage: 1, lastPage: 1, perPage: 25, total: 1 };
const query = { sort: 'name', search: '', filter: {}, tab: null, per_page: 25 };

const page = (can = {}, attributes = {}, abilities = {}) =>
    render(
        <Index
            rows={{
                data: [
                    {
                        id: 3,
                        name: 'Bia',
                        email: 'bia@example.com',
                        profile_name: 'Financeiro',
                        is_active: true,
                        confirmed: true,
                        ...attributes,
                        can: {
                            update: false,
                            status: false,
                            delete: false,
                            permissions: false,
                            passwordChange: false,
                            authenticationEmail: false,
                            restore: false,
                            ...can,
                        },
                    },
                ],
                meta,
            }}
            query={query}
            tabs={[]}
            summaries={{}}
            abilities={{ create: false, export: false, bulk: [], ...abilities }}
            profiles={[]}
        />,
    );

const confirmAction = async () => {
    expect(await screen.findByRole('dialog')).toBeTruthy();

    fireEvent.click(screen.getByRole('button', { name: 'Confirmar' }));
};

describe('ações da listagem de usuários', () => {
    beforeEach(() =>
        setPageProps({
            translations,
            auth: {
                user: { id: 1, name: 'Ana', email: 'ana@example.com' },
                isAdministrator: false,
                permissions: [],
            },
        }),
    );

    test('oferece novo usuário somente com a habilidade', () => {
        const { unmount } = page({}, {}, { create: true });

        expect(screen.getByRole('link', { name: 'Novo usuário' })).toBeTruthy();

        unmount();
        page();

        expect(screen.queryByRole('link', { name: 'Novo usuário' })).toBeNull();
    });

    test('toda ação tem ícone e descrição', () => {
        page({ update: true, permissions: true, passwordChange: true, authenticationEmail: true, delete: true, restore: true });

        expectDescribedActions();
    });

    test('editar aparece conforme o can do registro', () => {
        page({ update: true });

        expect(screen.getByRole('link', { name: 'Editar' })).toBeTruthy();
        expect(screen.queryByRole('button', { name: 'Excluir' })).toBeNull();
    });

    test('excluir pede confirmação', async () => {
        page({ delete: true });

        fireEvent.click(screen.getByRole('button', { name: 'Excluir' }));

        expect(router.delete).not.toHaveBeenCalled();

        await confirmAction();

        expect(router.delete).toHaveBeenCalledOnce();
    });

    test('inativar pede confirmação e envia a situação', async () => {
        page({ status: true });

        fireEvent.click(screen.getByRole('switch', { name: 'Situação de Bia' }));

        expect(router.patch).not.toHaveBeenCalled();

        await confirmAction();

        expect(router.patch.mock.calls[0][1]).toEqual({ is_active: false });
    });

    test('usuário inativo oferece ativar', () => {
        page({ status: true }, { is_active: false });

        const toggle = screen.getByRole('switch', { name: 'Situação de Bia' });

        expect(toggle.checked).toBe(false);
    });

    test('permissões aparece conforme o can do registro', () => {
        const { unmount } = page({ permissions: true });

        expect(screen.getByRole('link', { name: 'Permissões' })).toBeTruthy();

        unmount();
        page();

        expect(screen.queryByRole('link', { name: 'Permissões', hidden: false })).toBeNull();
    });

    const openMenu = () => {
        const trigger = screen.getByRole('button', { name: 'Mais ações' });

        fireEvent.click(trigger);
    };

    test('solicitar troca de senha pede confirmação', async () => {
        page({ passwordChange: true });
        openMenu();

        fireEvent.click(
            await screen.findByRole('menuitem', { name: 'Solicitar troca de senha' }),
        );

        expect(router.post).not.toHaveBeenCalled();

        await confirmAction();

        expect(router.post).toHaveBeenCalledOnce();
    });

    test('enviar e-mail de autenticação pede confirmação', async () => {
        page({ authenticationEmail: true }, { confirmed: false });
        openMenu();

        fireEvent.click(
            await screen.findByRole('menuitem', { name: 'Enviar e-mail de autenticação' }),
        );

        expect(router.post).not.toHaveBeenCalled();

        await confirmAction();

        expect(router.post).toHaveBeenCalledOnce();
    });

    test('as ações de e-mail somem quando o can do registro nega', () => {
        page({ update: true });

        expect(screen.queryByRole('button', { name: 'Mais ações' })).toBeNull();
    });

    test('não tem violações de acessibilidade', async () => {
        const { container } = page(
            { update: true, delete: true, status: true },
            {},
            { create: true },
        );

        expect(await axe(container)).toHaveNoViolations();
    });
});
