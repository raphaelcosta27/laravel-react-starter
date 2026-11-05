import PermissionsIndex from '@/Pages/Admin/Permissions/Index';
import ProfilesIndex from '@/Pages/Admin/Profiles/Index';
import UsersIndex from '@/Pages/Admin/Users/Index';
import { router } from '@inertiajs/react';
import { fireEvent, render, screen } from '@testing-library/react';
import { beforeEach, describe, expect, test } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';

const meta = { currentPage: 1, lastPage: 1, perPage: 25, total: 1 };
const query = { sort: 'name', search: '', filter: {}, tab: 'all', per_page: 25 };
const tabs = [{ key: 'all', label: 'Todos', count: 1 }];

const user = (can = {}) => ({
    id: 3,
    name: 'Bia',
    email: 'bia@example.com',
    profile_name: 'Financeiro',
    is_active: true,
    confirmed: true,
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
});

const users = (can, abilities = {}) =>
    render(
        <UsersIndex
            rows={{ data: [user(can)], meta }}
            query={query}
            tabs={tabs}
            summaries={{}}
            abilities={{ create: false, export: false, bulk: [], ...abilities }}
            profiles={[]}
        />,
    );

const profile = (can = {}) => ({
    id: 3,
    name: 'Financeiro',
    description: 'Equipe',
    users_count: 2,
    permissions: [{ id: 1, title: 'Gerar', code: 'gerar' }],
    can: { update: false, delete: false, restore: false, users: false, ...can },
});

const profiles = (can, props = {}) =>
    render(
        <ProfilesIndex
            rows={{ data: [profile(can)], meta }}
            query={query}
            tabs={tabs}
            summaries={{ users_count: { sum: 7 } }}
            abilities={{ create: false, export: false }}
            {...props}
        />,
    );

const permissions = (can) =>
    render(
        <PermissionsIndex
            rows={{
                data: [
                    {
                        id: 3,
                        title: 'Gerar',
                        code: 'gerar',
                        description: 'Permite',
                        can: { update: false, delete: false, restore: false, ...can },
                    },
                ],
                meta,
            }}
            query={query}
            tabs={tabs}
            summaries={{}}
            abilities={{ create: false, export: false }}
        />,
    );

describe('recursos da tabela nas telas reais', () => {
    beforeEach(() => {
        localStorage.clear();
        window.history.replaceState({}, '', '/admin/x?page=1');
        setPageProps({
            translations,
            auth: {
                user: { id: 1, name: 'Ana', email: 'ana@example.com' },
                isAdministrator: false,
                permissions: [],
            },
        });
    });

    test('edição direta no nome do usuário, conforme o can', () => {
        const { unmount } = users({ update: true });

        expect(screen.getByLabelText('Nome de Bia').tagName).toBe('INPUT');
        expect(screen.queryByLabelText('E-mail de Bia')).toBeNull();

        unmount();
        users({});

        expect(screen.queryByLabelText('Nome de Bia')).toBeNull();
    });

    test('edição direta no nome e na descrição do perfil', () => {
        profiles({ update: true });

        expect(screen.getByLabelText('Nome de Financeiro')).toBeTruthy();
        expect(screen.getByLabelText('Descrição de Financeiro')).toBeTruthy();
    });

    test('edição direta no título e na descrição da permissão', () => {
        permissions({ update: true });

        expect(screen.getByLabelText('Título de Gerar')).toBeTruthy();
        expect(screen.getByLabelText('Descrição de Gerar')).toBeTruthy();
        expect(screen.queryByLabelText('Código de Gerar')).toBeNull();
    });

    test('a situação usa interruptor com confirmação', async () => {
        users({ status: true });

        fireEvent.click(screen.getByRole('switch', { name: 'Situação de Bia' }));

        expect(router.patch).not.toHaveBeenCalled();
        expect(await screen.findByRole('dialog')).toBeTruthy();

        fireEvent.click(screen.getByRole('button', { name: 'Confirmar' }));

        expect(router.patch.mock.calls[0][1]).toEqual({ is_active: false });
    });

    test('sem o can, o interruptor fica desativado', () => {
        users({});

        expect(screen.getByRole('switch', { name: 'Situação de Bia' }).disabled).toBe(true);
    });

    test('abas e exportação aparecem nas listagens', () => {
        users({}, { export: true });

        expect(screen.getByRole('tab', { name: 'Todos (1)' })).toBeTruthy();
        expect(screen.getByRole('button', { name: 'Exportar' })).toBeTruthy();
    });

    test('lote de usuários conforme as habilidades', async () => {
        users({}, { bulk: ['delete'] });

        fireEvent.click(screen.getByRole('checkbox', { name: 'Selecionar Bia' }));

        expect(screen.queryByRole('button', { name: 'Inativar selecionados' })).toBeNull();

        fireEvent.click(screen.getByRole('button', { name: 'Excluir selecionados' }));

        expect(await screen.findByRole('dialog')).toBeTruthy();

        fireEvent.click(screen.getByRole('button', { name: 'Confirmar' }));

        expect(router.post.mock.calls[0][1]).toEqual({ action: 'delete', ids: [3] });
    });

    test('sem habilidades de lote, não há seleção', () => {
        users({});

        expect(screen.queryByRole('checkbox', { name: 'Selecionar Bia' })).toBeNull();
    });

    test('a linha de detalhe do perfil exibe as permissões', () => {
        profiles({});

        fireEvent.click(screen.getByRole('button', { name: 'Exibir detalhes de Financeiro' }));

        expect(screen.getByText('Gerar')).toBeTruthy();
    });

    test('a listagem de perfis exibe o total de usuários', () => {
        profiles({});

        expect(screen.getByText('7')).toBeTruthy();
    });
});
