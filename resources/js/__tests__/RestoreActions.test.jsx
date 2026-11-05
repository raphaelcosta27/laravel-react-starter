import PermissionsIndex from '@/Pages/Admin/Permissions/Index';
import ProfilesIndex from '@/Pages/Admin/Profiles/Index';
import UsersIndex from '@/Pages/Admin/Users/Index';
import { router } from '@inertiajs/react';
import { fireEvent, render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';

const meta = { currentPage: 1, lastPage: 1, perPage: 25, total: 1 };
const query = { sort: 'name', search: '', filter: {}, tab: 'trashed', per_page: 25 };
const tabs = [{ key: 'trashed', label: 'Excluídos', count: 1 }];
const can = { update: false, delete: false, restore: true, status: false, users: false };

const shared = { query, tabs, summaries: {}, abilities: { create: false, export: false, bulk: [] } };

const users = (attributes = {}, props = {}) =>
    render(
        <UsersIndex
            {...shared}
            rows={{
                data: [
                    {
                        id: 3,
                        name: 'Bia',
                        email: 'bia@example.com',
                        profile_name: 'Financeiro',
                        is_active: true,
                        confirmed: true,
                        deleted_at: '05/11/2026 10:00',
                        profile_deleted: false,
                        can,
                        ...attributes,
                    },
                ],
                meta,
            }}
            profiles={[{ value: '2', label: 'Contas' }]}
            {...props}
        />,
    );

const confirm = () =>
    fireEvent.click(screen.getByRole('button', { name: 'Confirmar' }));

describe('restauração nas listagens', () => {
    beforeEach(() => {
        localStorage.clear();
        window.history.replaceState({}, '', '/admin/x?page=1');
        setPageProps({
            translations,
            auth: {
                user: { id: 1, name: 'Ana', email: 'ana@example.com' },
                isAdministrator: true,
                permissions: [],
            },
        });
    });

    test('a aba Excluídos exibe a data e a ação de restaurar', () => {
        users();

        expect(screen.getByRole('columnheader', { name: 'Excluído em' })).toBeTruthy();
        expect(screen.getByRole('cell', { name: '05/11/2026 10:00' })).toBeTruthy();
        expect(screen.getByRole('button', { name: 'Restaurar' })).toBeTruthy();
    });

    test('fora da aba Excluídos a data não aparece', () => {
        users({}, { query: { ...query, tab: 'all' } });

        expect(screen.queryByRole('columnheader', { name: 'Excluído em' })).toBeNull();
    });

    test('sem o can, restaurar não aparece', () => {
        users({ can: { ...can, restore: false } });

        expect(screen.queryByRole('button', { name: 'Restaurar' })).toBeNull();
    });

    test('restaurar usuário pede confirmação', async () => {
        users();

        fireEvent.click(screen.getByRole('button', { name: 'Restaurar' }));

        expect(router.post).not.toHaveBeenCalled();
        expect(await screen.findByRole('dialog')).toBeTruthy();

        confirm();

        expect(router.post).toHaveBeenCalledOnce();
    });

    test('perfil excluído exige a escolha de um perfil ativo', async () => {
        users({ profile_deleted: true });

        fireEvent.click(screen.getByRole('button', { name: 'Restaurar' }));

        const select = await screen.findByLabelText('Novo perfil do usuário');

        confirm();
        expect(router.post).not.toHaveBeenCalled();

        fireEvent.change(select, { target: { value: '2' } });
        confirm();

        expect(router.post.mock.calls[0][1]).toEqual({ profile_id: '2' });
    });

    test('a escolha de perfil não tem violações de acessibilidade', async () => {
        users({ profile_deleted: true });

        fireEvent.click(screen.getByRole('button', { name: 'Restaurar' }));
        await screen.findByRole('dialog');

        expect(await axe(document.body)).toHaveNoViolations();
    });

    test('restaurar perfil pede confirmação', async () => {
        render(
            <ProfilesIndex
                {...shared}
                rows={{
                    data: [
                        {
                            id: 3,
                            name: 'Financeiro',
                            description: '',
                            users_count: 0,
                            permissions: [],
                            deleted_at: '05/11/2026 10:00',
                            can,
                        },
                    ],
                    meta,
                }}
            />,
        );

        expect(screen.getByRole('cell', { name: '05/11/2026 10:00' })).toBeTruthy();

        fireEvent.click(screen.getByRole('button', { name: 'Restaurar' }));
        await screen.findByRole('dialog');
        confirm();

        expect(router.post).toHaveBeenCalledOnce();
    });

    test('restaurar permissão pede confirmação', async () => {
        render(
            <PermissionsIndex
                {...shared}
                rows={{
                    data: [
                        {
                            id: 3,
                            title: 'Gerar',
                            code: 'gerar',
                            description: 'Permite',
                            deleted_at: '05/11/2026 10:00',
                            can,
                        },
                    ],
                    meta,
                }}
            />,
        );

        expect(screen.getByRole('cell', { name: '05/11/2026 10:00' })).toBeTruthy();

        fireEvent.click(screen.getByRole('button', { name: 'Restaurar' }));
        await screen.findByRole('dialog');
        confirm();

        expect(router.post).toHaveBeenCalledOnce();
    });
});
