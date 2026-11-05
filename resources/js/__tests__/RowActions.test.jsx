import RowActions from '@/Components/DataTable/RowActions';
import { fireEvent, render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test, vi } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';

const row = { id: 1, name: 'Ana' };

const actions = (props = {}) =>
    render(<RowActions row={row} label="Ações de Ana" {...props} />);

describe('RowActions', () => {
    beforeEach(() => setPageProps({ translations }));

    test('onClick recebe os dados do registro', () => {
        const onClick = vi.fn();

        actions({ actions: [{ key: 'contrato', label: 'Enviar contrato', onClick }] });

        fireEvent.click(screen.getByRole('button', { name: 'Enviar contrato' }));

        expect(onClick).toHaveBeenCalledWith(row);
    });

    test('href navega como link', () => {
        actions({
            actions: [
                { key: 'editar', label: 'Editar', href: (r) => `/users/${r.id}` },
            ],
        });

        expect(
            screen.getByRole('link', { name: 'Editar' }).getAttribute('href'),
        ).toBe('/users/1');
    });

    test('visible esconde a ação conforme o registro', () => {
        actions({
            actions: [
                { key: 'a', label: 'Visível', onClick: () => {}, visible: () => true },
                { key: 'b', label: 'Oculta', onClick: () => {}, visible: () => false },
            ],
        });

        expect(screen.getByRole('button', { name: 'Visível' })).toBeTruthy();
        expect(screen.queryByRole('button', { name: 'Oculta' })).toBeNull();
    });

    test('disabled desativa a ação', () => {
        const onClick = vi.fn();

        actions({
            actions: [
                { key: 'a', label: 'Excluir', onClick, disabled: () => true },
            ],
        });

        const button = screen.getByRole('button', { name: 'Excluir' });

        expect(button.disabled).toBe(true);
        fireEvent.click(button);
        expect(onClick).not.toHaveBeenCalled();
    });

    test('confirm pede confirmação antes de executar', async () => {
        const onClick = vi.fn();

        actions({
            actions: [
                {
                    key: 'excluir',
                    label: 'Excluir',
                    onClick,
                    variant: 'danger',
                    confirm: { title: 'Excluir Ana?', message: 'Confirme' },
                },
            ],
        });

        fireEvent.click(screen.getByRole('button', { name: 'Excluir' }));

        expect(onClick).not.toHaveBeenCalled();
        expect(await screen.findByRole('dialog')).toBeTruthy();

        fireEvent.click(screen.getByRole('button', { name: 'Confirmar' }));

        expect(onClick).toHaveBeenCalledWith(row);
    });

    test('variant danger destaca a ação', () => {
        actions({
            actions: [
                { key: 'a', label: 'Excluir', onClick: () => {}, variant: 'danger' },
            ],
        });

        expect(screen.getByRole('button', { name: 'Excluir' }).className).toContain(
            'text-red',
        );
    });

    test('menu agrupa em "Mais ações", operável por teclado', async () => {
        const onClick = vi.fn();

        actions({
            actions: [
                { key: 'a', label: 'Reenviar', onClick, menu: true },
                { key: 'b', label: 'Editar', onClick: () => {} },
            ],
        });

        expect(screen.queryByRole('menuitem', { name: 'Reenviar' })).toBeNull();

        const trigger = screen.getByRole('button', { name: 'Mais ações' });

        trigger.focus();
        fireEvent.keyDown(trigger, { key: 'Enter' });

        const item = await screen.findByRole('menuitem', { name: 'Reenviar' });

        fireEvent.click(item);

        expect(onClick).toHaveBeenCalledWith(row);
    });

    test('o foco entra no diálogo aberto pelo menu', async () => {
        actions({
            actions: [
                {
                    key: 'a',
                    label: 'Reenviar',
                    onClick: () => {},
                    menu: true,
                    confirm: { title: 'Reenviar?', message: 'Confirme' },
                },
            ],
        });

        const trigger = screen.getByRole('button', { name: 'Mais ações' });

        trigger.focus();
        fireEvent.keyDown(trigger, { key: 'Enter' });
        fireEvent.click(await screen.findByRole('menuitem', { name: 'Reenviar' }));

        const dialog = await screen.findByRole('dialog');

        await new Promise((resolve) => setTimeout(resolve, 150));

        expect(dialog.contains(document.activeElement)).toBe(true);
    });

    test('sem ações visíveis nada é exibido', () => {
        const { container } = actions({ actions: [] });

        expect(container.textContent).toBe('');
    });

    test('não tem violações de acessibilidade', async () => {
        const { container } = actions({
            actions: [
                { key: 'a', label: 'Editar', href: () => '/x' },
                { key: 'b', label: 'Excluir', onClick: () => {} },
                { key: 'c', label: 'Reenviar', onClick: () => {}, menu: true },
            ],
        });

        expect(await axe(container)).toHaveNoViolations();
    });
});
