import DataTable from '@/Components/DataTable/DataTable';
import ToggleCell from '@/Components/DataTable/ToggleCell';
import { router } from '@inertiajs/react';
import EditIcon from '@mui/icons-material/Edit';
import { fireEvent, render, screen, within } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test, vi } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';

const columns = [
    { accessorKey: 'name', header: 'Nome', meta: { required: true, pinned: 'start' } },
    { accessorKey: 'email', header: 'E-mail' },
    { accessorKey: 'total', header: 'Total', meta: { align: 'end', summary: 'sum' } },
];

const data = [
    { id: 1, name: 'Ana', email: 'ana@example.com', total: 2 },
    { id: 2, name: 'Bia', email: 'bia@example.com', total: 3 },
];

const rows = { data, meta: { currentPage: 1, lastPage: 1, perPage: 25, total: 2 } };
const query = { sort: 'name', search: 'ana', filter: {}, tab: 'all', per_page: 25 };

const table = (props = {}) =>
    render(
        <DataTable
            tableKey="extras"
            caption="Registros"
            columns={columns}
            rows={rows}
            query={query}
            emptyMessage="Vazio"
            {...props}
        />,
    );

const lastVisit = () => router.get.mock.calls.at(-1);

describe('recursos completos do DataTable', () => {
    beforeEach(() => {
        localStorage.clear();
        window.history.replaceState({}, '', '/admin/extras?page=1');
        setPageProps({ translations, auth: { user: { id: 7 } } });
    });

    test('recarregar os dados mantém o botão da linha, para o foco voltar a ele', () => {
        const rowActions = () => [
            { key: 'x', label: 'Ver', icon: EditIcon, onClick: () => {} },
        ];

        const props = {
            tableKey: 'extras',
            caption: 'Registros',
            rows,
            query,
            emptyMessage: 'Vazio',
            rowActions,
        };

        const { rerender } = render(<DataTable {...props} columns={columns} />);

        const button = screen.getAllByRole('button', { name: 'Ver' })[0];

        rerender(
            <DataTable
                {...props}
                columns={[...columns]}
                rows={{ ...rows, data: data.map((row) => ({ ...row })) }}
            />,
        );

        expect(button.isConnected).toBe(true);
    });

    test('abas exibem a quantidade e mudam a listagem', () => {
        table({
            tabs: [
                { key: 'all', label: 'Todos', count: 5 },
                { key: 'trashed', label: 'Excluídos', count: 2 },
            ],
        });

        const list = screen.getByRole('tablist');
        const selected = within(list).getByRole('tab', { name: 'Todos (5)' });

        expect(selected.getAttribute('aria-selected')).toBe('true');

        fireEvent.click(within(list).getByRole('tab', { name: 'Excluídos (2)' }));

        expect(lastVisit()[1]).toMatchObject({ tab: 'trashed' });
        expect(lastVisit()[1].page).toBeUndefined();
    });

    test('abas navegam pelas setas', () => {
        table({
            tabs: [
                { key: 'all', label: 'Todos', count: 5 },
                { key: 'trashed', label: 'Excluídos', count: 2 },
            ],
        });

        screen.getByRole('tab', { name: 'Todos (5)' }).focus();

        fireEvent.keyDown(screen.getByRole('tab', { name: 'Todos (5)' }), {
            key: 'ArrowRight',
        });

        expect(lastVisit()[1]).toMatchObject({ tab: 'trashed' });
    });

    test('seleção rotulada pelo registro e ação em lote', async () => {
        const onClick = vi.fn();

        table({
            bulkActions: [
                {
                    key: 'delete',
                    label: 'Excluir selecionados',
                    onClick,
                    confirm: { title: 'Excluir', message: 'Confirme' },
                },
            ],
        });

        expect(screen.queryByRole('button', { name: 'Excluir selecionados' })).toBeNull();

        fireEvent.click(screen.getByRole('checkbox', { name: 'Selecionar Ana' }));

        expect(screen.getByText('1 selecionado(s)')).toBeTruthy();

        fireEvent.click(screen.getByRole('button', { name: 'Excluir selecionados' }));

        expect(onClick).not.toHaveBeenCalled();
        expect(await screen.findByRole('dialog')).toBeTruthy();

        fireEvent.click(screen.getByRole('button', { name: 'Confirmar' }));

        expect(onClick).toHaveBeenCalledWith([data[0]]);
    });

    test('selecionar todos marca os registros da página', () => {
        table({ bulkActions: [{ key: 'a', label: 'Agir', onClick: () => {} }] });

        fireEvent.click(screen.getByRole('checkbox', { name: 'Selecionar todos' }));

        expect(screen.getByText('2 selecionado(s)')).toBeTruthy();
    });

    test('linha de detalhe usa botão com aria-expanded', () => {
        table({ renderDetail: (row) => <p>Detalhe de {row.name}</p> });

        const button = screen.getByRole('button', { name: 'Exibir detalhes de Ana' });

        expect(button.getAttribute('aria-expanded')).toBe('false');
        expect(screen.queryByText('Detalhe de Ana')).toBeNull();

        fireEvent.click(button);

        expect(button.getAttribute('aria-expanded')).toBe('true');
        expect(screen.getByText('Detalhe de Ana')).toBeTruthy();
        expect(screen.queryByText('Detalhe de Bia')).toBeNull();
    });

    test('ToggleCell usa role switch e informa a mudança', () => {
        const onChange = vi.fn();

        render(<ToggleCell checked label="Situação de Ana" onChange={onChange} />);

        const toggle = screen.getByRole('switch', { name: 'Situação de Ana' });

        expect(toggle.checked).toBe(true);

        fireEvent.click(toggle);

        expect(onChange).toHaveBeenCalledWith(false);
    });

    test('ToggleCell desativado não muda', () => {
        const onChange = vi.fn();

        render(<ToggleCell checked disabled label="Situação" onChange={onChange} />);

        expect(screen.getByRole('switch', { name: 'Situação' }).disabled).toBe(true);
    });

    test('coluna com toggle usa o interruptor', () => {
        const onToggle = vi.fn();

        table({
            columns: [
                ...columns,
                {
                    accessorKey: 'is_active',
                    header: 'Situação',
                    meta: { toggle: { onToggle, label: (row) => `Situação de ${row.name}` } },
                },
            ],
            rows: { ...rows, data: data.map((row) => ({ ...row, is_active: true })) },
        });

        fireEvent.click(screen.getByRole('switch', { name: 'Situação de Bia' }));

        expect(onToggle.mock.calls[0][0]).toMatchObject({ id: 2 });
        expect(onToggle.mock.calls[0][1]).toBe(false);
    });

    test('nenhuma célula de texto vira campo de edição', () => {
        table();

        expect(screen.getByRole('cell', { name: 'Ana' })).toBeTruthy();
        expect(screen.queryByRole('textbox', { name: /Ana/ })).toBeNull();
    });

    test('totais aparecem no rodapé', () => {
        table({ summaries: { total: { sum: 5 } } });

        const footer = screen.getAllByRole('rowgroup').at(-1);

        expect(footer.tagName).toBe('TFOOT');
        expect(within(footer).getByText('5')).toBeTruthy();
    });

    test('coluna fixa fica presa ao início', () => {
        table();

        expect(screen.getByRole('cell', { name: 'Ana' }).style.position).toBe('sticky');
        expect(screen.getByRole('cell', { name: 'ana@example.com' }).style.position).not.toBe('sticky');
    });

    test('com coluna fixa, cabeçalho e células seguem a mesma ordem', () => {
        table({
            bulkActions: [{ key: 'a', label: 'Agir', onClick: () => {} }],
            renderDetail: (row) => <p>{row.name}</p>,
            rowActions: () => [{ key: 'x', label: 'Ver', icon: EditIcon, onClick: () => {} }],
        });

        const headers = screen.getAllByRole('columnheader');
        const cells = within(screen.getAllByRole('row')[1]).getAllByRole('cell');

        expect(headers).toHaveLength(cells.length);
        expect(headers[0].querySelector('input[type=checkbox]')).toBeTruthy();
        expect(cells[0].querySelector('input[type=checkbox]')).toBeTruthy();
        expect(headers[2].textContent).toBe('Nome');
        expect(cells[2].textContent).toBe('Ana');
    });

    test('colunas de controle ficam fixas antes da coluna fixa', () => {
        table({ bulkActions: [{ key: 'a', label: 'Agir', onClick: () => {} }] });

        const select = screen.getByRole('checkbox', { name: 'Selecionar Ana' }).closest('td');
        const name = screen.getByRole('cell', { name: 'Ana' });

        expect(select.style.position).toBe('sticky');
        expect(name.style.insetInlineStart).toBe(select.style.width);

        // Sem margem interna, a caixa cabe na largura que desloca a coluna seguinte.
        expect(getComputedStyle(select).paddingLeft).toBe('0px');
        expect(getComputedStyle(select).paddingRight).toBe('0px');
    });

    test('o arraste da borda fica fora da ordem de tabulação', () => {
        const { container } = table();

        const handle = container.querySelector('[data-resize-handle]');

        expect(handle).toBeTruthy();
        expect(handle.getAttribute('aria-hidden')).toBe('true');
        expect(handle.hasAttribute('tabindex')).toBe(false);
        expect(handle.tagName).not.toBe('BUTTON');
    });

    test('ocultar coluna e guardar a escolha', () => {
        table();

        fireEvent.click(screen.getByRole('button', { name: 'Colunas' }));
        fireEvent.click(screen.getByRole('checkbox', { name: 'E-mail' }));

        expect(screen.queryByRole('columnheader', { name: 'E-mail' })).toBeNull();
        expect(
            JSON.parse(localStorage.getItem('datatable:7:extras:v1')).columnVisibility,
        ).toEqual({ email: false });
    });

    test('a ordem guardada é aplicada na primeira renderização', () => {
        localStorage.setItem(
            'datatable:7:extras:v1',
            JSON.stringify({ columnOrder: ['email', 'total', 'name'] }),
        );

        table();

        const headers = screen
            .getAllByRole('columnheader')
            .map((header) => header.textContent);

        expect(headers.indexOf('E-mail')).toBeLessThan(headers.indexOf('Total'));
    });

    test('exportação usa as colunas visíveis e a consulta atual', async () => {
        window.route = globalThis.route;

        table({ canExport: true, exportRoute: 'admin.users.export' });

        const trigger = screen.getByRole('button', { name: 'Exportar' });

        fireEvent.click(trigger);

        const href = (await screen.findByRole('menuitem', { name: 'CSV' })).getAttribute('href');
        const params = new URL(href, 'http://localhost').searchParams;

        expect(params.get('format')).toBe('csv');
        expect(params.getAll('columns[]')).toEqual(['name', 'email', 'total']);
        expect(params.get('search')).toBe('ana');
    });

    test('exportar selecionados envia os registros marcados', async () => {
        table({
            canExport: true,
            exportRoute: 'admin.users.export',
            bulkActions: [{ key: 'a', label: 'Agir', onClick: () => {} }],
        });

        fireEvent.click(screen.getByRole('checkbox', { name: 'Selecionar Bia' }));

        const trigger = screen.getByRole('button', { name: 'Exportar' });

        fireEvent.click(trigger);

        const href = (
            await screen.findByRole('menuitem', { name: 'CSV dos selecionados' })
        ).getAttribute('href');

        expect(new URL(href, 'http://localhost').searchParams.getAll('ids[]')).toEqual(['2']);
    });

    test('sem permissão a exportação não aparece', () => {
        table({ canExport: false, exportRoute: 'admin.users.export' });

        expect(screen.queryByRole('button', { name: 'Exportar' })).toBeNull();
    });

    test('não tem violações de acessibilidade', async () => {
        const { container } = table({
            tabs: [{ key: 'all', label: 'Todos', count: 2 }],
            bulkActions: [{ key: 'a', label: 'Agir', onClick: () => {} }],
            renderDetail: (row) => <p>{row.name}</p>,
            summaries: { total: { sum: 5 } },
            canExport: true,
            exportRoute: 'admin.users.export',
        });

        fireEvent.click(screen.getByRole('button', { name: 'Exibir detalhes de Ana' }));
        fireEvent.click(screen.getByRole('checkbox', { name: 'Selecionar Ana' }));

        expect(await axe(container)).toHaveNoViolations();
    });
});
