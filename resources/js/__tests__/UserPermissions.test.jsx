import Permissions from '@/Pages/Admin/Users/Permissions';
import { router } from '@inertiajs/react';
import { fireEvent, render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';

const permissions = [
    { id: 1, title: 'Do Perfil', code: 'a', active: true, origin: 'profile', adjustment: null },
    { id: 2, title: 'Concedida', code: 'b', active: true, origin: 'grant', adjustment: 'grant' },
    { id: 3, title: 'Revogada', code: 'c', active: false, origin: 'revoke', adjustment: 'revoke' },
    { id: 4, title: 'Sem Vínculo', code: 'd', active: false, origin: 'none', adjustment: null },
];

const page = (props = {}) =>
    render(
        <Permissions
            user={{ id: 5, name: 'Bia' }}
            permissions={permissions}
            canAdjust
            {...props}
        />,
    );

describe('permissões do usuário', () => {
    beforeEach(() =>
        setPageProps({
            translations,
            auth: {
                user: { id: 1, name: 'Ana', email: 'ana@example.com' },
                isAdministrator: true,
                permissions: [],
            },
        }),
    );

    test('a origem é exibida em texto', () => {
        page();

        expect(screen.getByRole('heading', { level: 1, name: 'Permissões de Bia' })).toBeTruthy();
        expect(screen.getByText('Vem do perfil')).toBeTruthy();
        expect(screen.getByText('Concessão individual')).toBeTruthy();
        expect(screen.getByText('Revogação individual')).toBeTruthy();
        expect(screen.getByText('Sem acesso')).toBeTruthy();
    });

    test('os controles são nativos e enviam os ajustes', () => {
        page();

        const select = screen.getByLabelText('Ajuste de Sem Vínculo');

        expect(select.tagName).toBe('SELECT');

        fireEvent.change(select, { target: { value: 'grant' } });
        fireEvent.click(screen.getByRole('button', { name: 'Salvar' }));

        expect(router.put.mock.calls[0][1]).toEqual({
            adjustments: [
                { permission_id: 2, effect: 'grant' },
                { permission_id: 3, effect: 'revoke' },
                { permission_id: 4, effect: 'grant' },
            ],
        });
    });

    test('sem ajustes disponíveis, os controles não aparecem', () => {
        page({ canAdjust: false });

        expect(screen.queryByRole('combobox')).toBeNull();
        expect(screen.queryByRole('button', { name: 'Salvar' })).toBeNull();
    });

    test('não tem violações de acessibilidade', async () => {
        const { container } = page();

        expect(await axe(container)).toHaveNoViolations();
    });
});
