import UserFormDialog from '@/Pages/Admin/Users/Partials/UserFormDialog';
import { fireEvent, render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { router, setPageProps } from './helpers/inertia';

const profileOptions = [
    { value: '2', label: 'Financeiro' },
    { value: '3', label: 'Usuário' },
];

const creating = {
    mode: 'create',
    record: null,
    profileOptions,
    canChangeProfile: true,
    canChangeEmail: true,
};

const editing = {
    mode: 'edit',
    record: { id: 5, name: 'Bia', email: 'bia@example.com', profile_id: 2 },
    profileOptions,
    canChangeProfile: false,
    canChangeEmail: false,
};

describe('formulário de usuário', () => {
    beforeEach(() => setPageProps({ translations }));

    test('oferece nome, e-mail e perfil obrigatório, sem senha', () => {
        render(<UserFormDialog form={creating} />);

        expect(screen.getByRole('dialog', { name: 'Novo usuário' })).toBeTruthy();
        expect(screen.getByLabelText(/^Nome/).required).toBe(true);
        expect(screen.getByLabelText(/^E-mail/).required).toBe(true);
        expect(screen.getByLabelText(/^Perfil/).required).toBe(true);
        expect(screen.queryByLabelText(/Senha/)).toBeNull();
    });

    test('lista somente os perfis recebidos', () => {
        render(<UserFormDialog form={creating} />);

        expect(screen.getByRole('option', { name: 'Financeiro' })).toBeTruthy();
        expect(screen.queryByRole('option', { name: 'Administrador' })).toBeNull();
    });

    test('quem não pode trocar o perfil vê o campo desativado', () => {
        render(<UserFormDialog form={editing} />);

        expect(screen.getByRole('dialog', { name: 'Editar usuário' })).toBeTruthy();
        expect(screen.getByLabelText(/^Perfil/).disabled).toBe(true);
    });

    test('quem não pode trocar o e-mail vê o campo desativado, com o motivo', () => {
        render(<UserFormDialog form={editing} />);

        expect(screen.getByLabelText(/^E-mail/).disabled).toBe(true);
        expect(
            screen.getByText('Somente administradores alteram o e-mail de uma conta.'),
        ).toBeTruthy();
    });

    test('quem pode trocar o e-mail vê o campo liberado', () => {
        render(<UserFormDialog form={{ ...editing, canChangeEmail: true }} />);

        expect(screen.getByLabelText(/^E-mail/).disabled).toBe(false);
    });

    test('exibe erros por campo', () => {
        setPageProps({ errors: { email: 'E-mail já usado' } });

        render(<UserFormDialog form={creating} />);

        expect(screen.getByText('E-mail já usado')).toBeTruthy();
    });

    test('fechar volta à listagem sem recarregar a tabela', () => {
        window.history.replaceState({}, '', '/admin/users/5/edit?search=ana&page=2');

        render(<UserFormDialog form={editing} />);

        fireEvent.click(screen.getByRole('button', { name: 'Cancelar' }));

        expect(router.visit).toHaveBeenCalledWith(
            '/admin.users.index?search=ana&page=2',
            { only: ['form'], preserveState: true, preserveScroll: true },
        );

        window.history.replaceState({}, '', '/');
    });

    test('não tem violações de acessibilidade', async () => {
        const { baseElement } = render(<UserFormDialog form={creating} />);

        expect(await axe(baseElement)).toHaveNoViolations();
    });
});
