import Form from '@/Pages/Admin/Users/Form';
import { render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';

const profileOptions = [
    { value: '2', label: 'Financeiro' },
    { value: '3', label: 'Usuário' },
];

const auth = {
    user: { id: 1, name: 'Ana', email: 'ana@example.com' },
    isAdministrator: true,
    permissions: [],
};

describe('formulário de usuário', () => {
    beforeEach(() => setPageProps({ translations, auth }));

    test('oferece nome, e-mail e perfil obrigatório, sem senha', () => {
        render(<Form user={null} profileOptions={profileOptions} canChangeProfile />);

        expect(screen.getByRole('heading', { level: 1, name: 'Novo usuário' })).toBeTruthy();
        expect(screen.getByLabelText('Nome').required).toBe(true);
        expect(screen.getByLabelText('E-mail').required).toBe(true);
        expect(screen.getByLabelText('Perfil').required).toBe(true);
        expect(screen.queryByLabelText(/Senha/)).toBeNull();
    });

    test('lista somente os perfis recebidos', () => {
        render(<Form user={null} profileOptions={profileOptions} canChangeProfile />);

        expect(screen.getByRole('option', { name: 'Financeiro' })).toBeTruthy();
        expect(screen.queryByRole('option', { name: 'Administrador' })).toBeNull();
    });

    test('quem não pode trocar o perfil vê o campo desativado', () => {
        render(
            <Form
                user={{ id: 5, name: 'Bia', email: 'bia@example.com', profile_id: 2 }}
                profileOptions={profileOptions}
                canChangeProfile={false}
            />,
        );

        expect(screen.getByRole('heading', { level: 1, name: 'Editar usuário' })).toBeTruthy();
        expect(screen.getByLabelText('Perfil').disabled).toBe(true);
    });

    test('exibe erros por campo', () => {
        setPageProps({ errors: { email: 'E-mail já usado' } });

        render(<Form user={null} profileOptions={profileOptions} canChangeProfile />);

        expect(screen.getByText('E-mail já usado')).toBeTruthy();
    });

    test('não tem violações de acessibilidade', async () => {
        const { container } = render(
            <Form user={null} profileOptions={profileOptions} canChangeProfile />,
        );

        expect(await axe(container)).toHaveNoViolations();
    });
});
