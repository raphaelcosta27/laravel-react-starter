import DataTable from '@/Components/DataTable/DataTable';
import DailyChart from '@/Components/Dashboard/DailyChart';
import Dashboard from '@/Pages/Dashboard';
import { render, screen, within } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test } from 'vitest';
import { setPageProps } from './helpers/inertia';

const block = (key, data, extra = {}) => ({
    key,
    title: key,
    failed: false,
    data,
    ...extra,
});

const series = [
    { date: '2026-11-05', value: 0 },
    { date: '2026-11-06', value: 3 },
];

const everyBlock = [
    block('ProfileCompletion', {
        percent: 40,
        missing: [{ key: 'contact', label: 'Add a contact', url: '/profile#contacts' }],
    }),
    block('Notifications', {
        unread: 2,
        items: [
            {
                id: 1,
                title: 'Aviso interno',
                link: '/profile',
                is_new: true,
                created_at: '2026-11-06T10:00:00Z',
            },
            {
                id: 2,
                title: 'Aviso sem link',
                link: null,
                is_new: false,
                created_at: '2026-11-06T09:00:00Z',
            },
        ],
    }),
    block('MyActivity', {
        items: [
            {
                entity: 'Contato',
                action: 'Alterado',
                fields: ['Telefone', 'WhatsApp'],
                created_at: '2026-11-06T10:00:00Z',
            },
        ],
    }),
    block('UsersOverview', {
        metrics: [
            { key: 'active', label: 'Ativos', value: 9, url: '/admin/users?tab=active' },
            { key: 'deleted', label: 'Excluídos', value: 2, url: null },
        ],
    }),
    block('PendingAccounts', {
        metrics: [{ key: 'a', label: 'Troca pendente', value: 1, url: '/a' }],
    }),
    block('ProfileDistribution', {
        metrics: [{ key: 'b', label: 'Gestores', value: 0, url: '/b' }],
    }),
    block('Accesses', { days: 30, active: 4, without_record: 6 }),
    block('UserGrowth', { days: 2, series, total: 3 }),
    block('SystemMovement', { days: 2, series, total: 3 }),
];

const auth = {
    user: { id: 1, name: 'Ana', email: 'ana@example.com' },
    displayName: 'Ana',
    permissions: [],
};

describe('Dashboard', () => {
    beforeEach(() => setPageProps({ auth }));

    test('monta cada bloco pela chave, com título próprio', () => {
        render(<Dashboard blocks={everyBlock} />);

        everyBlock
            .filter(({ key }) => key !== 'UsersOverview')
            .forEach(({ key }) => {
                expect(screen.getByRole('heading', { level: 3, name: key })).toBeTruthy();
            });
    });

    test('agrupa os blocos em seções, sem título de seção vazia', () => {
        render(<Dashboard blocks={everyBlock.slice(0, 3)} />);

        expect(screen.getByRole('heading', { level: 2, name: 'Your account' })).toBeTruthy();
        expect(screen.queryByRole('heading', { level: 2, name: 'Overview' })).toBeNull();
        expect(screen.queryByRole('heading', { level: 2, name: 'Details' })).toBeNull();
    });

    test('ignora bloco sem componente', () => {
        render(<Dashboard blocks={[block('Desconhecido', {})]} />);

        expect(screen.queryByRole('heading', { name: 'Desconhecido' })).toBeNull();
    });

    test('bloco com falha mostra aviso sem afetar os demais', () => {
        render(
            <Dashboard
                blocks={[
                    block('UsersOverview', null, { failed: true }),
                    block('Accesses', { days: 30, active: 4, without_record: 6 }),
                ]}
            />,
        );

        expect(screen.getByText('This block could not be loaded.')).toBeTruthy();
        expect(screen.getByText('Without recorded access')).toBeTruthy();
    });

    test('a tela com todos os blocos não tem violações', async () => {
        const { container } = render(<Dashboard blocks={everyBlock} />);

        expect(await axe(container)).toHaveNoViolations();
    });

    test('perfil incompleto mostra percentual e link do que falta', () => {
        render(<Dashboard blocks={[everyBlock[0]]} />);

        expect(screen.getByRole('progressbar').getAttribute('aria-valuenow')).toBe('40');
        expect(screen.getByText('40%')).toBeTruthy();
        expect(screen.getByRole('link', { name: 'Add a contact' }).getAttribute('href')).toBe(
            '/profile#contacts',
        );
    });

    test('perfil completo não lista pendências', () => {
        render(
            <Dashboard blocks={[block('ProfileCompletion', { percent: 100, missing: [] })]} />,
        );

        expect(screen.getByText('Your profile is complete.')).toBeTruthy();
    });

    test('notificações mostram a quantidade e o link só quando existe', () => {
        render(<Dashboard blocks={[everyBlock[1]]} />);

        expect(screen.getByText('Unread: 2')).toBeTruthy();
        expect(screen.getByRole('link', { name: 'Aviso interno' })).toBeTruthy();
        expect(screen.queryByRole('link', { name: 'Aviso sem link' })).toBeNull();
    });

    test('blocos vazios explicam a situação', () => {
        render(
            <Dashboard
                blocks={[
                    block('Notifications', { unread: 0, items: [] }),
                    block('MyActivity', { items: [] }),
                ]}
            />,
        );

        expect(screen.getByText('You have no notifications.')).toBeTruthy();
        expect(screen.getByText('No recent activity.')).toBeTruthy();
    });

    test('atividade mostra entidade, ação e campos', () => {
        render(<Dashboard blocks={[everyBlock[2]]} />);

        expect(screen.getByText('Contato · Alterado')).toBeTruthy();
        expect(screen.getByText(/Telefone · WhatsApp/)).toBeTruthy();
    });

    test('métrica com endereço vira link, e sem endereço fica em texto', () => {
        render(<Dashboard blocks={[everyBlock[3]]} />);

        expect(screen.getByRole('link', { name: /Ativos/ }).getAttribute('href')).toBe(
            '/admin/users?tab=active',
        );
        expect(screen.queryByRole('link', { name: /Excluídos/ })).toBeNull();
        expect(screen.getByText('Excluídos')).toBeTruthy();
    });
});

describe('DailyChart', () => {
    test('a tabela traz os mesmos valores, inclusive os dias com zero', () => {
        render(<DailyChart label="Novos" days={2} series={series} total={3} />);

        const rows = within(screen.getByRole('table', { hidden: true })).getAllByRole('row', {
            hidden: true,
        });

        expect(rows).toHaveLength(3);
        expect(rows[1].textContent).toBe('05/110');
        expect(rows[2].textContent).toBe('06/113');
    });

    test('sem movimento mostra mensagem, sem gráfico vazio', () => {
        render(
            <DailyChart
                label="Novos"
                days={2}
                series={series.map(({ date }) => ({ date, value: 0 }))}
                total={0}
            />,
        );

        expect(screen.getByText('No data in this period.')).toBeTruthy();
        expect(screen.queryByRole('table', { hidden: true })).toBeNull();
    });

    test('não tem violações', async () => {
        const { container } = render(
            <DailyChart label="Novos" days={2} series={series} total={3} />,
        );

        expect(await axe(container)).toHaveNoViolations();
    });
});

describe('coluna oculta por padrão', () => {
    const columns = [
        { accessorKey: 'name', header: 'Nome' },
        {
            accessorKey: 'pending',
            header: 'Pendente',
            meta: { filter: 'boolean', defaultHidden: true },
        },
    ];

    beforeEach(() => {
        localStorage.clear();
        setPageProps({ auth });
    });

    const table = () =>
        render(
            <DataTable
                tableKey="teste"
                columns={columns}
                rows={{
                    data: [{ id: 1, name: 'Ana', pending: 'sim' }],
                    meta: { currentPage: 1, lastPage: 1, perPage: 25, total: 1 },
                }}
                query={{ filter: {}, sort: 'name', search: '', tab: 'all', per_page: 25 }}
                caption="Pessoas"
                emptyMessage="Vazio"
            />,
        );

    test('começa oculta', () => {
        table();

        expect(screen.queryByRole('columnheader', { name: /Pendente/ })).toBeNull();
    });

    test('a preferência salva prevalece', () => {
        localStorage.setItem(
            'datatable:1:teste:v1',
            JSON.stringify({ columnVisibility: { pending: true } }),
        );

        table();

        expect(screen.getByRole('columnheader', { name: /Pendente/ })).toBeTruthy();
    });
});
