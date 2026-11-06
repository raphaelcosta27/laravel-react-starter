import DataTable from '@/Components/DataTable/DataTable';
import EditIcon from '@mui/icons-material/Edit';
import { fireEvent, render, screen, within } from '@testing-library/react';
import { axe } from 'jest-axe';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { router, setPageProps } from './helpers/inertia';

const columns = [
    { accessorKey: 'name', header: 'Nome', meta: { sortable: true, essential: true } },
    { accessorKey: 'email', header: 'E-mail', meta: { essential: true } },
    { accessorKey: 'city', header: 'Cidade' },
    { accessorKey: 'total', header: 'Total', meta: { summary: 'sum' } },
];

const data = [
    { id: 1, name: 'Ana', email: 'ana@example.com', city: 'Recife', total: 2 },
    { id: 2, name: 'Bia', email: 'bia@example.com', city: 'Olinda', total: 3 },
];

const rows = { data, meta: { currentPage: 1, lastPage: 1, perPage: 25, total: 2 } };

const table = (props = {}) =>
    render(
        <DataTable
            tableKey="cards"
            caption="Registros"
            columns={columns}
            rows={rows}
            query={{ filter: {}, per_page: 25 }}
            emptyMessage="Vazio"
            {...props}
        />,
    );

const card = (name) => screen.getByText(name).closest('li');

describe('tabela no celular', () => {
    beforeEach(() => {
        localStorage.clear();
        window.history.replaceState({}, '', '/admin/cards?page=1');
        setPageProps({ translations, auth: { user: { id: 7 } } });
        vi.stubGlobal('matchMedia', (query) => ({
            matches: query.includes('max-width'),
            media: query,
            addEventListener: () => {},
            removeEventListener: () => {},
            addListener: () => {},
            removeListener: () => {},
        }));
    });

    afterEach(() => vi.unstubAllGlobals());

    test('troca a tabela por cartões só com os campos essenciais', () => {
        table();

        expect(screen.queryByRole('table')).toBeNull();
        expect(screen.getByRole('list', { name: 'Registros' })).toBeTruthy();
        expect(within(card('Ana')).getByText('ana@example.com')).toBeTruthy();
        expect(within(card('Ana')).getByText('E-mail')).toBeTruthy();
        expect(screen.queryByText('Recife')).toBeNull();
    });

    test('sem coluna marcada, valem as três primeiras', () => {
        table({
            columns: columns.map(({ meta, ...column }) => ({
                ...column,
                meta: { ...meta, essential: false },
            })),
        });

        expect(screen.getByText('Recife')).toBeTruthy();
        expect(screen.queryByText('2')).toBeNull();
    });

    test('uma coluna oculta no computador continua no cartão', () => {
        localStorage.setItem(
            'datatable:7:cards:v1',
            JSON.stringify({ columnVisibility: { email: false } }),
        );

        table();

        expect(screen.getByText('ana@example.com')).toBeTruthy();
    });

    test('não oferece colunas nem totais', () => {
        table({ summaries: { total: { sum: 5 } } });

        expect(screen.queryByRole('button', { name: 'Colunas' })).toBeNull();
        expect(screen.queryByText('Total')).toBeNull();
    });

    test('mantém as ações do registro', () => {
        const onClick = vi.fn();

        table({
            rowActions: () => [
                { key: 'x', label: 'Ver', description: 'Abre.', icon: EditIcon, onClick },
            ],
        });

        fireEvent.click(within(card('Bia')).getByRole('button', { name: 'Ver' }));

        expect(onClick).toHaveBeenCalledWith(data[1]);
    });

    test('mantém a seleção, inclusive de todos', () => {
        table({
            bulkActions: [
                { key: 'a', label: 'Agir', description: 'Age.', icon: EditIcon, onClick: () => {} },
            ],
        });

        fireEvent.click(screen.getByRole('checkbox', { name: 'Selecionar Ana' }));
        expect(screen.getByText('1 selecionado(s)')).toBeTruthy();

        fireEvent.click(screen.getByRole('checkbox', { name: 'Selecionar todos' }));
        expect(screen.getByText('2 selecionado(s)')).toBeTruthy();
    });

    test('abre os detalhes no cartão', () => {
        table({ renderDetail: (row) => <p>Detalhe de {row.name}</p> });

        fireEvent.click(screen.getByRole('button', { name: 'Exibir detalhes de Ana' }));

        expect(within(card('Ana')).getByText('Detalhe de Ana')).toBeTruthy();
    });

    test('o interruptor continua funcionando', () => {
        const onToggle = vi.fn();

        table({
            columns: [
                columns[0],
                {
                    accessorKey: 'active',
                    header: 'Situação',
                    meta: {
                        essential: true,
                        toggle: { label: (row) => `Situação de ${row.name}`, onToggle },
                    },
                },
            ],
        });

        fireEvent.click(screen.getByRole('switch', { name: 'Situação de Ana' }));

        expect(onToggle).toHaveBeenCalledWith(data[0], true);
    });

    test('ordena pelo seletor e volta à ordem padrão', () => {
        table({ query: { filter: {}, per_page: 25, sort: 'name' } });

        const select = screen.getByLabelText('Ordenar por');

        expect(select.value).toBe('name');

        fireEvent.change(select, { target: { value: '-name' } });
        expect(router.get.mock.calls.at(-1)[1]).toMatchObject({ sort: '-name' });

        fireEvent.change(select, { target: { value: '' } });
        expect(router.get.mock.calls.at(-1)[1].sort).toBeUndefined();
    });

    test('sem coluna ordenável, não mostra o seletor', () => {
        table({ columns: [columns[1], columns[2]] });

        expect(screen.queryByLabelText('Ordenar por')).toBeNull();
    });

    test('tabela vazia mostra a mensagem, sem cartões', () => {
        table({ rows: { ...rows, data: [] } });

        expect(screen.getByText('Vazio')).toBeTruthy();
        expect(screen.queryByRole('list', { name: 'Registros' })).toBeNull();
    });

    test('não tem violações', async () => {
        const { container } = table({
            renderDetail: () => <p>Detalhe</p>,
            rowActions: () => [
                { key: 'x', label: 'Ver', description: 'Abre.', icon: EditIcon, onClick: () => {} },
            ],
            bulkActions: [
                { key: 'a', label: 'Agir', description: 'Age.', icon: EditIcon, onClick: () => {} },
            ],
        });

        expect(await axe(container)).toHaveNoViolations();
    });
});
