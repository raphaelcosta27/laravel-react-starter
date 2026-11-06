import DataTable from '@/Components/DataTable/DataTable';
import EditIcon from '@mui/icons-material/Edit';
import { fireEvent, render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';

const columns = [
    { accessorKey: 'name', header: 'Nome', meta: { filter: 'text', required: true } },
    { accessorKey: 'email', header: 'E-mail' },
];

const rows = (meta = {}) => ({
    data: [{ id: 1, name: 'Ana', email: 'ana@example.com' }],
    meta: { currentPage: 1, lastPage: 1, perPage: 25, total: 1, ...meta },
});

const table = (props = {}) =>
    render(
        <DataTable
            tableKey="icons"
            caption="Registros"
            columns={columns}
            rows={rows()}
            query={{ filter: {}, per_page: 25 }}
            emptyMessage="Vazio"
            exportRoute="admin.users.export"
            canExport
            {...props}
        />,
    );

const describedBy = (button) =>
    document.getElementById(button.getAttribute('aria-describedby'))?.textContent;

describe('botões de ícone da tabela', () => {
    beforeEach(() => {
        localStorage.clear();
        window.history.replaceState({}, '', '/admin/icons?page=1');
        setPageProps({ translations, auth: { user: { id: 7 } } });
    });

    test.each([
        'Buscar',
        'Filtros',
        'Restaurar padrão',
        'Colunas',
        'Exportar',
        'Página anterior',
        'Próxima página',
    ])('%s é um ícone com título e descrição', (name) => {
        table();

        const button = screen.getByRole('button', { name });

        expect(button.querySelector('svg')).toBeTruthy();
        expect(button.innerText ?? '').toBe('');
        expect(describedBy(button)).toBeTruthy();
    });

    test('a lupa envia a busca pelo formulário', () => {
        table();

        expect(screen.getByRole('button', { name: 'Buscar' }).getAttribute('type')).toBe('submit');
    });

    test('filtros aplicados aparecem na quantidade e liberam a limpeza', () => {
        table({ query: { filter: { name: 'ana' }, per_page: 25 } });

        const filters = screen.getByRole('button', { name: 'Filtros (1)' });

        expect(filters.querySelector('.MuiBadge-badge').textContent).toBe('1');
        expect(describedBy(screen.getByRole('button', { name: 'Limpar filtros' }))).toBeTruthy();
    });

    test('sem filtros, o botão não mostra quantidade', () => {
        table();

        expect(
            screen
                .getByRole('button', { name: 'Filtros' })
                .querySelector('.MuiBadge-invisible'),
        ).toBeTruthy();
    });

    test('a paginação desativa o lado sem páginas', () => {
        table({ rows: rows({ currentPage: 2, lastPage: 2 }) });

        expect(screen.getByRole('button', { name: 'Próxima página' }).disabled).toBe(true);
        expect(screen.getByRole('button', { name: 'Página anterior' }).disabled).toBe(false);
    });

    test('ações em massa seguem o mesmo padrão', () => {
        table({
            bulkActions: [
                {
                    key: 'a',
                    label: 'Agir',
                    description: 'Age sobre os selecionados.',
                    icon: EditIcon,
                    onClick: () => {},
                },
            ],
        });

        fireEvent.click(screen.getByRole('checkbox', { name: 'Selecionar Ana' }));

        const button = screen.getByRole('button', { name: 'Agir' });

        expect(button.querySelector('svg')).toBeTruthy();
        expect(describedBy(button)).toBe('Age sobre os selecionados.');
    });

    test('não tem violações', async () => {
        const { container } = table({ query: { filter: { name: 'ana' }, per_page: 25 } });

        expect(await axe(container)).toHaveNoViolations();
    });
});
