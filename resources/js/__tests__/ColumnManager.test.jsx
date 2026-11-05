import ColumnManager from '@/Components/DataTable/ColumnManager';
import { fireEvent, render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test, vi } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';

const columns = [
    { id: 'name', label: 'Nome', required: true },
    { id: 'email', label: 'E-mail' },
    { id: 'status', label: 'Situação' },
];

const manager = (props = {}) => {
    const handlers = {
        onVisibilityChange: vi.fn(),
        onOrderChange: vi.fn(),
        onSizeChange: vi.fn(),
    };

    const view = render(
        <ColumnManager
            columns={columns}
            order={['name', 'email', 'status']}
            visibility={{}}
            sizing={{}}
            {...handlers}
            {...props}
        />,
    );

    fireEvent.click(screen.getByRole('button', { name: 'Colunas' }));

    return { ...view, ...handlers };
};

describe('ColumnManager', () => {
    beforeEach(() => setPageProps({ translations }));

    test('o painel abre pelo botão e indica o estado', () => {
        render(
            <ColumnManager
                columns={columns}
                order={[]}
                visibility={{}}
                sizing={{}}
            />,
        );

        const button = screen.getByRole('button', { name: 'Colunas' });

        expect(button.getAttribute('aria-expanded')).toBe('false');

        fireEvent.click(button);

        expect(button.getAttribute('aria-expanded')).toBe('true');
    });

    test('oculta e exibe colunas', () => {
        const { onVisibilityChange } = manager({ visibility: { status: false } });

        fireEvent.click(screen.getByRole('checkbox', { name: 'E-mail' }));
        expect(onVisibilityChange).toHaveBeenLastCalledWith({ status: false, email: false });

        fireEvent.click(screen.getByRole('checkbox', { name: 'Situação' }));
        expect(onVisibilityChange).toHaveBeenLastCalledWith({ status: true });
    });

    test('coluna obrigatória não pode ser ocultada', () => {
        manager();

        expect(screen.getByRole('checkbox', { name: 'Nome' }).disabled).toBe(true);
    });

    test('move colunas por botões', () => {
        const { onOrderChange } = manager();

        fireEvent.click(screen.getByRole('button', { name: 'Mover E-mail para a esquerda' }));
        expect(onOrderChange).toHaveBeenLastCalledWith(['email', 'name', 'status']);

        fireEvent.click(screen.getByRole('button', { name: 'Mover E-mail para a direita' }));
        expect(onOrderChange).toHaveBeenLastCalledWith(['name', 'status', 'email']);
    });

    test('os limites desativam o movimento', () => {
        manager();

        expect(screen.getByRole('button', { name: 'Mover Nome para a esquerda' }).disabled).toBe(true);
        expect(screen.getByRole('button', { name: 'Mover Situação para a direita' }).disabled).toBe(true);
    });

    test('altera a largura por botões', () => {
        const { onSizeChange } = manager({ sizing: { email: 200 } });

        fireEvent.click(screen.getByRole('button', { name: 'Aumentar largura de E-mail' }));
        expect(onSizeChange).toHaveBeenLastCalledWith({ email: 220 });

        fireEvent.click(screen.getByRole('button', { name: 'Diminuir largura de E-mail' }));
        expect(onSizeChange).toHaveBeenLastCalledWith({ email: 180 });
    });

    test('a largura não fica abaixo do mínimo', () => {
        const { onSizeChange } = manager({ sizing: { email: 60 } });

        fireEvent.click(screen.getByRole('button', { name: 'Diminuir largura de E-mail' }));

        expect(onSizeChange).toHaveBeenLastCalledWith({ email: 60 });
    });

    test('não tem violações de acessibilidade', async () => {
        const { container } = manager();

        expect(await axe(container)).toHaveNoViolations();
    });
});
