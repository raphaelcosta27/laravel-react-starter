import DataTable from '@/Components/DataTable/DataTable';
import { router } from '@inertiajs/react';
import {
    fireEvent,
    render,
    screen,
    waitFor,
    within,
} from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';

const columns = [
    {
        accessorKey: 'name',
        header: 'Nome',
        meta: {
            sortable: true,
            filter: 'text',
            required: true,
            align: 'start',
            wrap: 'truncate',
        },
    },
    {
        accessorKey: 'email',
        header: 'E-mail',
        meta: { sortable: true, align: 'end', wrap: 'nowrap' },
    },
    {
        accessorKey: 'is_active',
        header: 'Situação',
        cell: ({ row }) => (row.original.is_active ? 'Ativo' : 'Inativo'),
        meta: { filter: 'boolean' },
    },
];

const rows = (data, meta = {}) => ({
    data,
    meta: { currentPage: 1, lastPage: 1, perPage: 25, total: data.length, ...meta },
});

const ana = { id: 1, name: 'Ana', email: 'ana@example.com', is_active: true };
const bia = { id: 2, name: 'Bia', email: 'bia@example.com', is_active: false };

const query = {
    sort: 'name',
    search: '',
    filter: {},
    tab: null,
    per_page: 25,
};

const table = (props = {}) =>
    render(
        <DataTable
            tableKey="users"
            caption="Usuários"
            columns={columns}
            rows={rows([ana, bia])}
            query={query}
            emptyMessage="Nenhum usuário encontrado"
            {...props}
        />,
    );

const lastVisit = () => router.get.mock.calls.at(-1);

describe('DataTable', () => {
    beforeEach(() => {
        localStorage.clear();
        window.history.replaceState({}, '', '/admin/users?page=1');
        setPageProps({ translations, auth: { user: { id: 7 } } });
    });

    test('usa marcação semântica', () => {
        table();

        const element = screen.getByRole('table', { name: 'Usuários' });

        expect(element.tagName).toBe('TABLE');
        expect(element.querySelector('caption').textContent).toBe('Usuários');
        expect(
            screen.getByRole('columnheader', { name: /E-mail/ }).getAttribute('scope'),
        ).toBe('col');
        expect(screen.getByRole('cell', { name: 'Inativo' })).toBeTruthy();
    });

    test('indica a ordenação em aria-sort', () => {
        table({ query: { ...query, sort: '-name' } });

        expect(
            screen.getByRole('columnheader', { name: /Nome/ }).getAttribute('aria-sort'),
        ).toBe('descending');
        expect(
            screen.getByRole('columnheader', { name: /E-mail/ }).getAttribute('aria-sort'),
        ).toBe('none');
        expect(
            screen
                .getByRole('columnheader', { name: 'Situação' })
                .hasAttribute('aria-sort'),
        ).toBe(false);
    });

    test('alterna a ordenação pelo botão do cabeçalho', () => {
        table();

        fireEvent.click(screen.getByRole('button', { name: 'Nome' }));

        expect(lastVisit()[1]).toMatchObject({ sort: '-name' });

        fireEvent.click(screen.getByRole('button', { name: 'E-mail' }));

        expect(lastVisit()[1]).toMatchObject({ sort: 'email' });
    });

    test('Shift acrescenta uma coluna à ordenação', () => {
        table();

        fireEvent.click(screen.getByRole('button', { name: 'E-mail' }), {
            shiftKey: true,
        });

        expect(lastVisit()[1]).toMatchObject({ sort: 'name,email' });
    });

    test('busca pelo termo e volta à página 1', () => {
        table({ rows: rows([ana], { currentPage: 3, lastPage: 3 }) });

        fireEvent.change(screen.getByRole('searchbox', { name: 'Buscar' }), {
            target: { value: 'ana' },
        });
        fireEvent.submit(screen.getByRole('search'));

        expect(lastVisit()[1]).toMatchObject({ search: 'ana' });
        expect(lastVisit()[1].page).toBeUndefined();
    });

    test('aplica filtros e exibe a contagem de ativos', () => {
        table({
            query: {
                ...query,
                filter: { name: { op: 'contains', value: 'ana' } },
            },
        });

        expect(screen.getByRole('button', { name: /Filtros \(1\)/ })).toBeTruthy();

        fireEvent.click(screen.getByRole('button', { name: /Filtros/ }));
        fireEvent.change(screen.getByLabelText('Situação'), {
            target: { value: '1' },
        });
        fireEvent.click(screen.getByRole('button', { name: 'Aplicar filtros' }));

        expect(lastVisit()[1].filter).toEqual({
            name: { op: 'contains', value: 'ana' },
            is_active: '1',
        });
    });

    test('limpa os filtros', () => {
        table({ query: { ...query, filter: { is_active: '1' } } });

        fireEvent.click(screen.getByRole('button', { name: 'Limpar filtros' }));

        expect(lastVisit()[1].filter).toBeUndefined();
    });

    test('não oferece limpar sem filtros ativos', () => {
        table();

        expect(screen.queryByRole('button', { name: 'Limpar filtros' })).toBeNull();
    });

    test('restaura o padrão removendo as preferências', () => {
        localStorage.setItem(
            'datatable:7:users:v1',
            JSON.stringify({ perPage: 10 }),
        );
        table();

        fireEvent.click(screen.getByRole('button', { name: 'Restaurar padrão' }));

        expect(localStorage.getItem('datatable:7:users:v1')).toBeNull();
        expect(lastVisit()[1]).toEqual({});
    });

    test('pagina e informa o total', () => {
        table({ rows: rows([ana], { currentPage: 2, lastPage: 3, total: 60 }) });

        expect(screen.getByText('60 registros')).toBeTruthy();

        fireEvent.click(screen.getByRole('button', { name: 'Próxima página' }));

        expect(lastVisit()[1]).toMatchObject({ page: 3 });

        fireEvent.click(screen.getByRole('button', { name: 'Página anterior' }));

        expect(lastVisit()[1].page).toBeUndefined();
    });

    test('desativa a navegação nos limites', () => {
        table();

        expect(
            screen.getByRole('button', { name: 'Página anterior' }).disabled,
        ).toBe(true);
        expect(
            screen.getByRole('button', { name: 'Próxima página' }).disabled,
        ).toBe(true);
    });

    test('muda a quantidade por página e volta à página 1', () => {
        table({ rows: rows([ana], { currentPage: 2, lastPage: 3 }) });

        fireEvent.change(screen.getByLabelText('Registros por página'), {
            target: { value: '10' },
        });

        expect(lastVisit()[1]).toMatchObject({ per_page: 10 });
        expect(lastVisit()[1].page).toBeUndefined();
    });

    test('guarda as escolhas no navegador', () => {
        table();

        fireEvent.change(screen.getByLabelText('Registros por página'), {
            target: { value: '10' },
        });

        expect(
            JSON.parse(localStorage.getItem('datatable:7:users:v1')),
        ).toMatchObject({ perPage: 10 });
    });

    test('exibe a mensagem de lista vazia', () => {
        table({ rows: rows([]) });

        expect(screen.getByText('Nenhum usuário encontrado')).toBeTruthy();
    });

    test('indica carregamento durante a visita', () => {
        table();

        fireEvent.click(screen.getByRole('button', { name: 'Nome' }));
        lastVisit()[2].onStart();

        return waitFor(() =>
            expect(
                screen.getByRole('table', { name: 'Usuários' }).getAttribute('aria-busy'),
            ).toBe('true'),
        );
    });

    test('reaplica as preferências com router.get e replace ao abrir sem parâmetros', () => {
        window.history.replaceState({}, '', '/admin/users');
        localStorage.setItem(
            'datatable:7:users:v1',
            JSON.stringify({ perPage: 10, sort: '-email', page: 2 }),
        );

        table();

        expect(lastVisit()[0]).toBe('/admin/users');
        expect(lastVisit()[1]).toEqual({ per_page: 10, sort: '-email', page: 2 });
        expect(lastVisit()[2]).toMatchObject({ replace: true });
    });

    test('parâmetros na URL têm prioridade sobre as preferências', () => {
        localStorage.setItem(
            'datatable:7:users:v1',
            JSON.stringify({ perPage: 10 }),
        );

        table();

        expect(router.get).not.toHaveBeenCalled();
    });

    test('não faz nova visita sem preferências guardadas', () => {
        window.history.replaceState({}, '', '/admin/users');

        table();

        expect(router.get).not.toHaveBeenCalled();
    });

    test('aplica a regra de estilo por registro', () => {
        table({
            rowSx: (row) => (row.is_active ? {} : { opacity: 0.5 }),
        });

        expect(
            getComputedStyle(screen.getByRole('row', { name: /Bia/ })).opacity,
        ).toBe('0.5');
        expect(
            getComputedStyle(screen.getByRole('row', { name: /Ana/ })).opacity,
        ).not.toBe('0.5');
    });

    test('aplica alinhamento e quebra de texto da coluna', () => {
        table();

        const email = getComputedStyle(
            screen.getByRole('cell', { name: 'ana@example.com' }),
        );
        const name = getComputedStyle(screen.getByRole('cell', { name: 'Ana' }));

        expect(email.textAlign).toBe('end');
        expect(email.whiteSpace).toBe('nowrap');
        expect(name.textOverflow).toBe('ellipsis');
    });

    test('aceita botões próprios acima da tabela', () => {
        table({ toolbar: <button type="button">Novo usuário</button> });

        expect(screen.getByRole('button', { name: 'Novo usuário' })).toBeTruthy();
    });

    test('o contêiner rolável recebe foco pelo teclado', () => {
        table();

        const region = screen.getByRole('region', { name: 'Usuários' });

        expect(region.getAttribute('tabindex')).toBe('0');
        expect(within(region).getByRole('table')).toBeTruthy();
    });

    test('não tem violações de acessibilidade', async () => {
        const { container } = table({
            query: { ...query, filter: { is_active: '1' } },
        });

        fireEvent.click(screen.getByRole('button', { name: /Filtros/ }));

        expect(await axe(container)).toHaveNoViolations();
    });

    test('lista vazia não tem violações de acessibilidade', async () => {
        const { container } = table({ rows: rows([]) });

        expect(await axe(container)).toHaveNoViolations();
    });
});
