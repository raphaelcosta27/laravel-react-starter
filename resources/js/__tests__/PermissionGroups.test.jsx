import PermissionGroups from '@/Components/PermissionGroups';
import { fireEvent, render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test, vi } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';

const modules = [
    {
        key: 'users',
        label: 'Usuários',
        view: 1,
        notice: null,
        permissions: [
            { id: 1, title: 'Visualizar', description: 'Vê a lista.', available: true },
            { id: 2, title: 'Editar', description: 'Altera.', available: false },
        ],
    },
    {
        key: 'environment',
        label: 'Ambiente',
        view: 3,
        notice: 'Módulo desligado.',
        permissions: [{ id: 3, title: 'Ver ambiente', description: 'Vê.', available: true }],
    },
];

const groups = (props = {}) =>
    render(
        <PermissionGroups modules={modules} checked={[1]} onToggle={() => {}} {...props} />,
    );

describe('PermissionGroups', () => {
    beforeEach(() => setPageProps({ translations, auth: { user: { id: 1 } } }));

    test('os módulos vêm recolhidos, e o título expande e recolhe', () => {
        groups();

        const title = screen.getByRole('button', { name: /Usuários/ });

        expect(title.getAttribute('aria-expanded')).toBe('false');

        fireEvent.click(title);
        expect(title.getAttribute('aria-expanded')).toBe('true');
        expect(screen.getByRole('checkbox', { name: 'Visualizar' })).toBeTruthy();

        fireEvent.click(title);
        expect(title.getAttribute('aria-expanded')).toBe('false');
    });

    test('o título é um botão real, alcançável pelo teclado', () => {
        groups();

        const title = screen.getByRole('button', { name: /Ambiente/ });

        title.focus();

        expect(document.activeElement).toBe(title);
    });

    test('o título mostra quantas estão marcadas', () => {
        groups();

        expect(screen.getByRole('button', { name: /Usuários.*1 de 2/ })).toBeTruthy();
        expect(screen.getByRole('button', { name: /Ambiente.*0 de 1/ })).toBeTruthy();
    });

    test('marcar avisa a tela', () => {
        const onToggle = vi.fn();

        groups({ onToggle });

        fireEvent.click(screen.getByRole('button', { name: /Usuários/ }));
        fireEvent.click(screen.getByRole('checkbox', { name: 'Visualizar' }));

        expect(onToggle).toHaveBeenCalledWith(1, false);
    });

    test('permissão indisponível fica bloqueada, com o motivo', () => {
        groups();

        fireEvent.click(screen.getByRole('button', { name: /Usuários/ }));

        const blocked = screen.getByRole('checkbox', { name: 'Editar' });

        expect(blocked.disabled).toBe(true);
        expect(
            document.getElementById(blocked.getAttribute('aria-describedby')).textContent,
        ).toBe('Você não possui esta permissão, por isso não pode alterá-la.');
    });

    test('mostra o aviso do módulo', () => {
        groups();

        fireEvent.click(screen.getByRole('button', { name: /Ambiente/ }));

        expect(screen.getByText('Módulo desligado.')).toBeTruthy();
    });

    test('não tem violações', async () => {
        const { container } = groups();

        fireEvent.click(screen.getByRole('button', { name: /Usuários/ }));

        expect(await axe(container)).toHaveNoViolations();
    });
});
