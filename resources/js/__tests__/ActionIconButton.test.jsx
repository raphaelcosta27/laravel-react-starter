import ActionIconButton from '@/Components/ActionIconButton';
import EditIcon from '@mui/icons-material/Edit';
import { act, fireEvent, render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { afterEach, describe, expect, test, vi } from 'vitest';

const button = (props = {}) =>
    render(
        <ActionIconButton
            icon={EditIcon}
            label="Editar"
            description="Abre o formulário."
            onClick={() => {}}
            {...props}
        />,
    );

describe('ActionIconButton', () => {
    afterEach(() => vi.useRealTimers());

    test('o título é o nome acessível', () => {
        button();

        expect(screen.getByRole('button', { name: 'Editar' })).toBeTruthy();
    });

    test('a descrição fica ligada ao botão mesmo com a dica fechada', () => {
        button();

        const element = screen.getByRole('button', { name: 'Editar' });

        expect(
            document.getElementById(element.getAttribute('aria-describedby'))
                .textContent,
        ).toBe('Abre o formulário.');
        expect(screen.queryByRole('tooltip')).toBeNull();
    });

    test('sem descrição não há referência vazia', () => {
        button({ description: undefined });

        expect(
            screen
                .getByRole('button', { name: 'Editar' })
                .getAttribute('aria-describedby'),
        ).toBeNull();
    });

    test('o mouse abre a dica com título e descrição', async () => {
        button();

        fireEvent.mouseOver(screen.getByRole('button', { name: 'Editar' }));

        const tooltip = await screen.findByRole('tooltip');

        expect(tooltip.textContent).toContain('Editar');
        expect(tooltip.textContent).toContain('Abre o formulário.');
    });

    test('sem descrição a dica traz só o título', async () => {
        button({ description: undefined });

        fireEvent.mouseOver(screen.getByRole('button', { name: 'Editar' }));

        expect((await screen.findByRole('tooltip')).textContent).toBe('Editar');
    });

    test('executa a ação ao clicar', () => {
        const onClick = vi.fn();

        button({ onClick });

        fireEvent.click(screen.getByRole('button', { name: 'Editar' }));

        expect(onClick).toHaveBeenCalledOnce();
    });

    test('com href é um link', () => {
        button({ href: '/usuarios/1/editar', onClick: undefined });

        expect(
            screen.getByRole('link', { name: 'Editar' }).getAttribute('href'),
        ).toBe('/usuarios/1/editar');
    });

    test('desabilitado continua com dica e não executa', async () => {
        const onClick = vi.fn();

        const { container } = button({ disabled: true, onClick });

        const element = screen.getByRole('button', { name: 'Editar' });

        expect(element.disabled).toBe(true);

        fireEvent.mouseOver(container.firstChild);

        expect(await screen.findByRole('tooltip')).toBeTruthy();
    });

    test('toque longo mostra a dica sem executar a ação', () => {
        vi.useFakeTimers();

        const onClick = vi.fn();

        button({ onClick });

        const element = screen.getByRole('button', { name: 'Editar' });

        fireEvent.touchStart(element);
        act(() => vi.advanceTimersByTime(700));
        fireEvent.touchEnd(element);
        fireEvent.click(element);

        expect(screen.getByRole('tooltip')).toBeTruthy();
        expect(onClick).not.toHaveBeenCalled();
    });

    test('toque curto executa a ação', () => {
        vi.useFakeTimers();

        const onClick = vi.fn();

        button({ onClick });

        const element = screen.getByRole('button', { name: 'Editar' });

        fireEvent.touchStart(element);
        act(() => vi.advanceTimersByTime(100));
        fireEvent.touchEnd(element);
        fireEvent.click(element);

        expect(onClick).toHaveBeenCalledOnce();
    });

    test('não tem violações de acessibilidade', async () => {
        const { container } = button();

        expect(await axe(container)).toHaveNoViolations();
    });
});
