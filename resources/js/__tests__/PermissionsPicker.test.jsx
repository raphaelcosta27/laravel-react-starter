import DataTable from '@/Components/DataTable/DataTable';
import Index from '@/Pages/Admin/Permissions/Index';
import { act, fireEvent, render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { router, setPageProps } from './helpers/inertia';

const meta = { currentPage: 1, lastPage: 1, perPage: 25, total: 3 };
const query = { sort: 'title', search: '', filter: {}, tab: null, per_page: 25 };

const data = [
    { id: 1, title: 'Ver perfis', code: 'a', module: 'profiles', module_label: 'Perfis', can: {} },
    { id: 2, title: 'Criar perfis', code: 'b', module: 'profiles', module_label: 'Perfis', can: {} },
    { id: 3, title: 'Ver usuários', code: 'c', module: 'users', module_label: 'Usuários', can: {} },
];

const page = (abilities = {}, props = {}) =>
    render(
        <Index
            rows={{ data, meta }}
            query={query}
            tabs={[]}
            summaries={{}}
            abilities={{ create: false, export: false, ...abilities }}
            modules={[{ value: 'users', label: 'Usuários' }]}
            {...props}
        />,
    );

const reply = (body) => ({ ok: true, status: 200, json: async () => body });

beforeEach(() => {
    localStorage.clear();
    window.history.replaceState({}, '', '/admin/permissions?page=1');
    setPageProps({
        translations,
        auth: { user: { id: 1, name: 'Ana', email: 'ana@example.com' }, isAdministrator: false, permissions: [] },
    });
});

describe('grupos da tela de permissões', () => {
    test('cada módulo tem um título com a quantidade', () => {
        page();

        expect(screen.getByRole('button', { name: 'Perfis (2)' })).toBeTruthy();
        expect(screen.getByRole('button', { name: 'Usuários (1)' })).toBeTruthy();
    });

    test('o título recolhe e expande os registros do módulo', () => {
        page();

        const title = screen.getByRole('button', { name: 'Perfis (2)' });

        fireEvent.click(title);

        expect(title.getAttribute('aria-expanded')).toBe('false');
        expect(screen.queryByText('Ver perfis')).toBeNull();
        expect(screen.getByText('Ver usuários')).toBeTruthy();

        fireEvent.click(title);

        expect(screen.getByText('Ver perfis')).toBeTruthy();
    });

    test('sem groupBy, não há título de grupo', () => {
        render(
            <DataTable
                tableKey="plain"
                caption="Registros"
                columns={[{ accessorKey: 'title', header: 'Título' }]}
                rows={{ data, meta }}
                query={query}
                emptyMessage="Vazio"
            />,
        );

        expect(screen.queryByRole('button', { name: /\(2\)/ })).toBeNull();
    });

    test('não tem violações', async () => {
        const { container } = page();

        expect(await axe(container)).toHaveNoViolations();
    });
});

describe('escolha de usuário', () => {
    beforeEach(() => {
        vi.useFakeTimers({ shouldAdvanceTime: true });
        vi.stubGlobal(
            'fetch',
            vi.fn(async () => reply([{ id: 8, name: 'Bia', email: 'bia@example.com' }])),
        );
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.unstubAllGlobals();
    });

    test('só aparece para quem gerencia', () => {
        page();

        expect(screen.queryByRole('button', { name: 'Permissões de usuário' })).toBeNull();
    });

    test('escolher abre o modal do usuário', async () => {
        page({ manageUsers: true });

        fireEvent.click(screen.getByRole('button', { name: 'Permissões de usuário' }));

        await act(async () => {
            await vi.advanceTimersByTimeAsync(400);
        });

        const field = screen.getByRole('combobox', { name: 'Usuário' });

        fireEvent.mouseDown(field);
        fireEvent.click(await screen.findByRole('option', { name: 'Bia (bia@example.com)' }));

        expect(router.visit.mock.calls.at(-1)[0]).toContain('/admin.permissions.users.edit');
    });

    test('abre o modal enviado pelo servidor', () => {
        page({}, {
            form: {
                mode: 'user-permissions',
                user: { id: 8, name: 'Bia' },
                canAdjust: true,
                origin: 'permissions',
                modules: [],
            },
        });

        expect(screen.getByRole('dialog', { name: 'Permissões de Bia' })).toBeTruthy();
    });
});
