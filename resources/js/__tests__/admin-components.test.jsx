import ConfirmDialog from '@/Components/ConfirmDialog';
import StatusAnnouncer from '@/Components/StatusAnnouncer';
import { useCan } from '@/lib/permissions';
import Dialog from '@mui/material/Dialog';
import {
    fireEvent,
    render,
    renderHook,
    screen,
    waitFor,
} from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test, vi } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';

const dialog = (props = {}) =>
    render(
        <ConfirmDialog
            show
            title="Excluir perfil"
            message="Esta ação pode ser desfeita pela restauração."
            onConfirm={() => {}}
            onClose={() => {}}
            {...props}
        />,
    );

describe('ConfirmDialog', () => {
    beforeEach(() => setPageProps({ translations }));

    test('exibe título e mensagem', async () => {
        dialog();

        expect(
            await screen.findByRole('dialog', { name: 'Excluir perfil' }),
        ).toBeTruthy();
        expect(
            screen.getByText('Esta ação pode ser desfeita pela restauração.'),
        ).toBeTruthy();
    });

    test('confirma e cancela', async () => {
        const onConfirm = vi.fn();
        const onClose = vi.fn();

        dialog({ onConfirm, onClose });
        await screen.findByRole('dialog');

        fireEvent.click(screen.getByRole('button', { name: 'Confirmar' }));
        expect(onConfirm).toHaveBeenCalledOnce();

        fireEvent.click(screen.getByRole('button', { name: 'Cancelar' }));
        expect(onClose).toHaveBeenCalledOnce();
    });

    test('sobre outro diálogo, o foco fica no diálogo de cima', async () => {
        render(
            <Dialog open aria-label="Formulário">
                <button type="button">Dentro do formulário</button>
                <ConfirmDialog
                    show
                    title="Descartar alterações"
                    onConfirm={() => {}}
                    onClose={() => {}}
                />
            </Dialog>,
        );

        const top = await screen.findByRole('dialog', {
            name: 'Descartar alterações',
        });

        await waitFor(() =>
            expect(top.contains(document.activeElement)).toBe(true),
        );

        screen.getByText('Dentro do formulário').focus();

        await waitFor(() =>
            expect(
                top
                    .closest('[role="presentation"]')
                    .contains(document.activeElement),
            ).toBe(true),
        );
    });

    test('Esc fecha somente o diálogo de confirmação', async () => {
        const onClose = vi.fn();
        const onOuterClose = vi.fn();

        render(
            <Dialog open aria-label="Formulário" onClose={onOuterClose}>
                <ConfirmDialog
                    show
                    title="Descartar alterações"
                    onConfirm={() => {}}
                    onClose={onClose}
                />
            </Dialog>,
        );

        const top = await screen.findByRole('dialog', {
            name: 'Descartar alterações',
        });

        fireEvent.keyDown(top, { key: 'Escape' });

        expect(onClose).toHaveBeenCalledOnce();
        expect(onOuterClose).not.toHaveBeenCalled();
    });

    test('o foco vai para dentro do diálogo', async () => {
        dialog();

        const element = await screen.findByRole('dialog');

        await vi.waitFor(() =>
            expect(element.contains(document.activeElement)).toBe(true),
        );
    });

    test('o foco volta para dentro se sair enquanto o diálogo está aberto', async () => {
        const outside = document.createElement('button');

        document.body.appendChild(outside);
        dialog();

        const element = await screen.findByRole('dialog');

        await vi.waitFor(() =>
            expect(element.contains(document.activeElement)).toBe(true),
        );

        outside.focus();

        await vi.waitFor(() =>
            expect(
                element
                    .closest('[role="presentation"]')
                    .contains(document.activeElement),
            ).toBe(true),
        );

        outside.remove();
    });

    test('não tem violações de acessibilidade', async () => {
        dialog();
        await screen.findByRole('dialog');

        expect(await axe(document.body)).toHaveNoViolations();
    });
});

describe('StatusAnnouncer', () => {
    test('anuncia em região aria-live polite', () => {
        render(<StatusAnnouncer message="Registro salvo" />);

        const region = screen.getByRole('status');

        expect(region.getAttribute('aria-live')).toBe('polite');
        expect(region.textContent).toBe('Registro salvo');
    });

    test('a região existe mesmo sem mensagem', () => {
        render(<StatusAnnouncer />);

        expect(screen.getByRole('status').textContent).toBe('');
    });

    test('exibe o motivo de uma ação recusada', () => {
        setPageProps({ errors: { profile: 'Perfil em uso' } });

        render(<StatusAnnouncer />);

        expect(screen.getByRole('status').textContent).toBe('Perfil em uso');
    });

    test('usa a mensagem compartilhada em flash', () => {
        setPageProps({ flash: { type: 'error', message: 'Falhou' } });

        render(<StatusAnnouncer />);

        expect(screen.getByRole('status').textContent).toBe('Falhou');
    });

    test('não tem violações de acessibilidade', async () => {
        const { container } = render(<StatusAnnouncer message="Salvo" />);

        expect(await axe(container)).toHaveNoViolations();
    });
});

describe('useCan', () => {
    const can = (code) => renderHook(() => useCan()).result.current(code);

    test('libera somente os códigos do usuário', () => {
        setPageProps({
            auth: { isAdministrator: false, permissions: ['editar_usuario'] },
        });

        expect(can('editar_usuario')).toBe(true);
        expect(can('excluir_usuario')).toBe(false);
    });

    test('libera tudo para administradores', () => {
        setPageProps({ auth: { isAdministrator: true, permissions: [] } });

        expect(can('codigo_qualquer')).toBe(true);
    });

    test('nega quando não há usuário', () => {
        setPageProps({ auth: { user: null } });

        expect(can('editar_usuario')).toBe(false);
    });
});
