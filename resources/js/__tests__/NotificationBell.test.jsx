import NotificationBell from '@/Components/NotificationBell';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    act,
    fireEvent,
    render,
    screen,
    waitFor,
    within,
} from '@testing-library/react';
import { axe } from 'jest-axe';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { setPageProps } from './helpers/inertia';

const item = (id, data = {}) => ({
    id,
    title: `Título ${id}`,
    body: `Texto ${id}`,
    type: 'info',
    link: null,
    is_new: false,
    created_at: '2026-11-05T10:00:00+00:00',
    ...data,
});

const reply = (body, status = 200) => ({
    status,
    ok: status < 400,
    json: async () => body,
});

let page;

const server = (url, options = {}) => {
    if (options.method === 'POST') {
        return Promise.resolve(reply({ unread: 0 }));
    }

    if (options.method === 'DELETE') {
        return Promise.resolve(reply({ unread: 0 }));
    }

    if (url.startsWith('/notifications.unread')) {
        return Promise.resolve(reply({ unread: 7 }));
    }

    return Promise.resolve(reply(page));
};

const open = async () => {
    fireEvent.click(screen.getByRole('button', { name: /^Notifications:/ }));
    await waitFor(() => expect(screen.queryByRole('progressbar')).toBeNull());
};

beforeEach(() => {
    page = { data: [], next_before: null, unread: 0 };
    setPageProps({ notifications: { unread: 0, pollSeconds: 60 } });
    vi.stubGlobal('fetch', vi.fn(server));
});

afterEach(() => {
    vi.useRealTimers();
    vi.unstubAllGlobals();
});

describe('sino', () => {
    test('mostra a quantidade e a anuncia no nome', () => {
        setPageProps({ notifications: { unread: 3, pollSeconds: 60 } });
        render(<NotificationBell />);

        expect(
            screen.getByRole('button', { name: 'Notifications: 3 new' }),
        ).toBeTruthy();
        expect(screen.getByText('3')).toBeTruthy();
    });

    test('uma nova fica no singular', () => {
        setPageProps({ notifications: { unread: 1, pollSeconds: 60 } });
        render(<NotificationBell />);

        expect(
            screen.getByRole('button', { name: 'Notifications: 1 new' }),
        ).toBeTruthy();
    });

    test('sem novas não mostra número', () => {
        render(<NotificationBell />);

        expect(
            screen.getByRole('button', { name: 'Notifications: none new' }),
        ).toBeTruthy();
    });

    test('acima de 99 mostra 99+', () => {
        setPageProps({ notifications: { unread: 120, pollSeconds: 60 } });
        render(<NotificationBell />);

        expect(
            screen.getByRole('button', { name: 'Notifications: 99+ new' }),
        ).toBeTruthy();
    });

    test('desligado não aparece', () => {
        setPageProps({ notifications: null });
        const { container } = render(<NotificationBell />);

        expect(container.innerHTML).toBe('');
    });

    test('atualiza o número sozinho no intervalo configurado', async () => {
        vi.useFakeTimers();
        setPageProps({ notifications: { unread: 0, pollSeconds: 30 } });
        render(<NotificationBell />);

        await act(async () => {
            await vi.advanceTimersByTimeAsync(30000);
        });

        expect(fetch.mock.calls[0][0]).toBe('/notifications.unread');
        expect(
            screen.getByRole('button', { name: 'Notifications: 7 new' }),
        ).toBeTruthy();
    });

    test('atualiza ao navegar', () => {
        const { rerender } = render(<NotificationBell />);

        setPageProps({ notifications: { unread: 4, pollSeconds: 60 } });
        rerender(<NotificationBell />);

        expect(
            screen.getByRole('button', { name: 'Notifications: 4 new' }),
        ).toBeTruthy();
    });

    test('faz parte do layout', () => {
        setPageProps({
            auth: { user: { id: 1, name: 'Ana', email: 'a@b.c' }, displayName: 'Ana', permissions: [] },
        });
        render(<AuthenticatedLayout title="Página">conteúdo</AuthenticatedLayout>);

        expect(screen.getByRole('button', { name: /^Notifications:/ })).toBeTruthy();
    });
});

describe('barra', () => {
    test('abre como diálogo e lista na ordem recebida', async () => {
        page = { ...page, data: [item(2), item(1)] };
        render(<NotificationBell />);

        await open();

        const dialog = within(screen.getByRole('dialog', { name: 'Notifications' }));
        const rows = dialog.getAllByRole('listitem');

        expect(within(rows[0]).getByText('Título 2')).toBeTruthy();
        expect(within(rows[1]).getByText('Título 1')).toBeTruthy();
    });

    test('sem notificações informa', async () => {
        render(<NotificationBell />);

        await open();

        expect(screen.getByText('You have no notifications.')).toBeTruthy();
    });

    test('marca como vistas as exibidas e zera o número', async () => {
        setPageProps({ notifications: { unread: 1, pollSeconds: 60 } });
        page = { data: [item(5, { is_new: true }), item(4)], next_before: null, unread: 1 };
        render(<NotificationBell />);

        await open();

        await waitFor(() =>
            expect(
                screen.getByRole('button', {
                    name: 'Notifications: none new',
                    hidden: true,
                }),
            ).toBeTruthy(),
        );

        const seen = fetch.mock.calls.find(([, options]) => options?.method === 'POST');

        expect(seen[0]).toBe('/notifications.seen');
        expect(JSON.parse(seen[1].body)).toEqual({ ids: [5] });
    });

    test('sem novas não marca nada', async () => {
        page = { ...page, data: [item(4)] };
        render(<NotificationBell />);

        await open();

        expect(
            fetch.mock.calls.some(([, options]) => options?.method === 'POST'),
        ).toBe(false);
    });

    test('distingue as novas por texto', async () => {
        page = { ...page, data: [item(5, { is_new: true }), item(4)] };
        render(<NotificationBell />);

        await open();

        const rows = screen.getAllByRole('listitem');

        expect(rows[0].textContent).toContain('New · Information');
        expect(rows[1].textContent).not.toContain('New');
    });

    test.each([
        ['info', 'Information'],
        ['success', 'Success'],
        ['warning', 'Warning'],
        ['error', 'Error'],
    ])('o tipo %s tem ícone e texto', async (type, label) => {
        page = { ...page, data: [item(1, { type })] };
        render(<NotificationBell />);

        await open();

        const row = screen.getByRole('listitem');

        expect(row.textContent).toContain(label);
        expect(row.querySelector('svg')).toBeTruthy();
    });

    test('carrega mais sem perder o que já estava na tela', async () => {
        page = { ...page, data: [item(3)], next_before: 3 };
        render(<NotificationBell />);

        await open();

        page = { ...page, data: [item(2, { is_new: true })], next_before: null };
        fireEvent.click(screen.getByRole('button', { name: 'Load more' }));

        await waitFor(() => expect(screen.getAllByRole('listitem')).toHaveLength(2));

        expect(
            fetch.mock.calls.some(([url]) => url.endsWith('?before=3')),
        ).toBe(true);
        expect(screen.queryByRole('button', { name: 'Load more' })).toBeNull();
    });

    test('o X oculta e leva o foco ao fechar', async () => {
        page = { ...page, data: [item(1)] };
        render(<NotificationBell />);

        await open();

        fireEvent.click(screen.getByRole('button', { name: 'Hide notification' }));

        await waitFor(() => expect(screen.queryByRole('listitem')).toBeNull());

        const call = fetch.mock.calls.find(([, options]) => options?.method === 'DELETE');

        expect(call[0]).toBe('/notifications.destroy');
        expect(document.activeElement).toBe(
            screen.getByRole('button', { name: 'Close notifications' }),
        );
    });

    test('o conteúdo é texto, sem interpretar marcação', async () => {
        page = { ...page, data: [item(1, { body: '<img src=x onerror=alert(1)>' })] };
        render(<NotificationBell />);

        await open();

        expect(screen.getByText('<img src=x onerror=alert(1)>')).toBeTruthy();
        expect(document.querySelector('img')).toBeNull();
    });

    test('o link leva à página indicada', async () => {
        page = { ...page, data: [item(1, { link: '/dashboard' })] };
        render(<NotificationBell />);

        await open();

        expect(screen.getByRole('link', { name: 'Open' }).getAttribute('href')).toBe(
            '/dashboard',
        );
    });

    test('uma falha é informada', async () => {
        fetch.mockResolvedValueOnce(reply({}, 500));
        render(<NotificationBell />);

        await open();

        expect(screen.getByRole('alert').textContent).toBe(
            'The notifications could not be loaded. Try again.',
        );
    });

    test('sessão vencida leva ao login', async () => {
        const assign = vi.fn();

        vi.stubGlobal('location', { assign });
        page = { ...page, data: [item(1)] };
        render(<NotificationBell />);

        await open();

        fetch.mockResolvedValueOnce(reply({}, 401));
        fireEvent.click(screen.getByRole('button', { name: 'Hide notification' }));

        await waitFor(() => expect(assign).toHaveBeenCalledWith('/login'));
    });

    test('não tem violações', async () => {
        page = { ...page, data: [item(1, { is_new: true, link: '/dashboard' })] };
        const { baseElement } = render(<NotificationBell />);

        await open();

        expect(await axe(baseElement)).toHaveNoViolations();
    });
});
