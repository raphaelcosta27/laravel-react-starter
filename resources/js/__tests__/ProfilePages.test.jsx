import ProfileFormDialog from '@/Pages/Admin/Profiles/Partials/ProfileFormDialog';
import Users from '@/Pages/Admin/Profiles/Users';
import { fireEvent, render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';

const permissions = [
    { id: 1, title: 'Gerar Relatório', code: 'gerar_relatorio', available: true },
    { id: 2, title: 'Exportar Dados', code: 'exportar_dados', available: true },
];

const withUnavailable = [
    permissions[0],
    { ...permissions[1], available: false },
];

const grouped = (items) => [
    { key: 'users', label: 'Usuários', view: null, notice: null, permissions: items },
];

const creating = { mode: 'create', record: null, modules: grouped(permissions), selected: [] };

const expand = () => fireEvent.click(screen.getByRole('button', { name: /Usuários/ }));

const meta = { currentPage: 1, lastPage: 1, perPage: 25, total: 1 };
const query = { sort: 'name', search: '', filter: {}, tab: null, per_page: 25 };

describe('telas de perfil', () => {
    beforeEach(() =>
        setPageProps({
            translations,
            auth: {
                user: { id: 1, name: 'Ana', email: 'ana@example.com' },
                isAdministrator: true,
                permissions: [],
            },
        }),
    );

    test('a seleção de permissões usa caixas nativas, operáveis pelo teclado', () => {
        render(
            <ProfileFormDialog
                form={{ mode: 'create', record: null, modules: grouped(permissions), selected: [1] }}
            />,
        );

        expand();

        const first = screen.getByRole('checkbox', { name: /Gerar Relatório/ });
        const second = screen.getByRole('checkbox', { name: /Exportar Dados/ });

        expect(first.checked).toBe(true);
        expect(second.checked).toBe(false);

        fireEvent.click(second);

        expect(second.checked).toBe(true);
    });

    test('o que o autor não possui fica indisponível e mantém o estado', async () => {
        const { container } = render(
            <ProfileFormDialog
                form={{
                    mode: 'create',
                    record: null,
                    modules: grouped(withUnavailable),
                    selected: [2],
                }}
            />,
        );

        expand();

        const unavailable = screen.getByRole('checkbox', { name: /Exportar Dados/ });

        expect(unavailable.disabled).toBe(true);
        expect(unavailable.checked).toBe(true);
        expect(screen.getByRole('checkbox', { name: /Gerar Relatório/ }).disabled).toBe(false);
        expect(
            screen.getByText('Você não possui esta permissão, por isso não pode alterá-la.'),
        ).toBeTruthy();
        expect(await axe(container)).toHaveNoViolations();
    });

    test('sem itens indisponíveis, o aviso não aparece', () => {
        render(<ProfileFormDialog form={creating} />);

        expect(
            screen.queryByText('Você não possui esta permissão, por isso não pode alterá-la.'),
        ).toBeNull();
    });

    test('o perfil master não oferece seleção de permissões', () => {
        render(
            <ProfileFormDialog
                form={{
                    mode: 'edit',
                    record: { id: 1, name: 'Administrador', is_administrator: true },
                    modules: grouped(permissions),
                    selected: [],
                }}
            />,
        );

        expect(screen.queryByRole('checkbox')).toBeNull();
        expect(screen.getByText('Este perfil tem acesso total ao sistema.')).toBeTruthy();
    });

    test('exibe erros por campo', () => {
        setPageProps({ errors: { name: 'Nome já usado' } });

        render(<ProfileFormDialog form={creating} />);

        expect(screen.getByText('Nome já usado')).toBeTruthy();
    });

    test('o formulário não tem violações de acessibilidade', async () => {
        const { baseElement } = render(<ProfileFormDialog form={creating} />);

        expect(await axe(baseElement)).toHaveNoViolations();
    });

    test('Usuários do perfil usa o DataTable', async () => {
        const { container } = render(
            <Users
                profile={{ id: 1, name: 'Financeiro' }}
                rows={{
                    data: [
                        {
                            id: 1,
                            name: 'Ana',
                            email: 'ana@example.com',
                            is_active: true,
                            can: {},
                        },
                    ],
                    meta,
                }}
                query={query}
            />,
        );

        expect(
            screen.getByRole('heading', { level: 1, name: 'Usuários do perfil Financeiro' }),
        ).toBeTruthy();
        expect(screen.getByRole('table')).toBeTruthy();
        expect(screen.getByRole('cell', { name: 'ana@example.com' })).toBeTruthy();
        expect(await axe(container)).toHaveNoViolations();
    });
});
