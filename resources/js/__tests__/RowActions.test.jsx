import RowActions from '@/Components/DataTable/RowActions';
import EditIcon from '@mui/icons-material/Edit';
import { fireEvent, render, screen, within } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test, vi } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';

const row = { id: 1, name: 'Ana' };

const complete = (action) => ({
    icon: EditIcon,
    description: `Descrição de ${action.label}`,
    ...action,
});

const actions = ({ actions: list = [], ...props } = {}) =>
    render(
        <RowActions
            row={row}
            label="Ações de Ana"
            actions={list.map(complete)}
            {...props}
        />,
    );

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
            'MuiIconButton-colorError',
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

        fireEvent.click(trigger);

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

        fireEvent.click(trigger);
        fireEvent.click(await screen.findByRole('menuitem', { name: 'Reenviar' }));

        const dialog = await screen.findByRole('dialog');

        await new Promise((resolve) => setTimeout(resolve, 150));

        expect(dialog.contains(document.activeElement)).toBe(true);
    });

    test('as ações em linha são ícones com descrição', () => {
        actions({ actions: [{ key: 'a', label: 'Editar', onClick: () => {} }] });

        const button = screen.getByRole('button', { name: 'Editar' });

        expect(button.querySelector('svg')).toBeTruthy();
        expect(button.textContent).toBe('');
        expect(
            document.getElementById(button.getAttribute('aria-describedby'))
                .textContent,
        ).toBe('Descrição de Editar');
    });

    test('os itens do menu trazem ícone, título e descrição', async () => {
        actions({
            actions: [
                { key: 'a', label: 'Reenviar', onClick: () => {}, menu: true },
            ],
        });

        fireEvent.click(screen.getByRole('button', { name: 'Mais ações' }));

        const item = await screen.findByRole('menuitem', { name: 'Reenviar' });

        expect(item.querySelector('svg')).toBeTruthy();
        expect(within(item).getByText('Descrição de Reenviar')).toBeTruthy();
        expect(
            document.getElementById(item.getAttribute('aria-describedby'))
                .textContent,
        ).toBe('Descrição de Reenviar');
    });

    test('"Mais ações" é um ícone que informa o menu', () => {
        actions({
            actions: [
                { key: 'a', label: 'Reenviar', onClick: () => {}, menu: true },
            ],
        });

        const trigger = screen.getByRole('button', { name: 'Mais ações' });

        expect(trigger.querySelector('svg')).toBeTruthy();
        expect(trigger.getAttribute('aria-haspopup')).toBe('menu');
        expect(trigger.getAttribute('aria-expanded')).toBe('false');
    });

    test('item desabilitado do menu não executa', async () => {
        const onClick = vi.fn();

        actions({
            actions: [
                {
                    key: 'a',
                    label: 'Reenviar',
                    onClick,
                    menu: true,
                    disabled: () => true,
                },
            ],
        });

        fireEvent.click(screen.getByRole('button', { name: 'Mais ações' }));

        const item = await screen.findByRole('menuitem', { name: 'Reenviar' });

        expect(item.getAttribute('aria-disabled')).toBe('true');
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
