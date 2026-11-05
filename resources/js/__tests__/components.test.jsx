import Checkbox from '@/Components/Checkbox';
import DangerButton from '@/Components/DangerButton';
import Dropdown from '@/Components/Dropdown';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import NavLink from '@/Components/NavLink';
import PrimaryButton from '@/Components/PrimaryButton';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { fireEvent, render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { describe, expect, test } from 'vitest';

const cases = {
    Checkbox: (
        <label>
            <Checkbox /> Lembrar
        </label>
    ),
    DangerButton: <DangerButton>Excluir</DangerButton>,
    InputError: <InputError message="Campo obrigatório" />,
    'InputLabel e TextInput': (
        <>
            <InputLabel htmlFor="nome" value="Nome" />
            <TextInput id="nome" />
        </>
    ),
    NavLink: (
        <nav>
            <NavLink href="/painel" active>
                Painel
            </NavLink>
        </nav>
    ),
    PrimaryButton: <PrimaryButton>Salvar</PrimaryButton>,
    ResponsiveNavLink: (
        <nav>
            <ResponsiveNavLink href="/painel">Painel</ResponsiveNavLink>
        </nav>
    ),
    SecondaryButton: <SecondaryButton>Cancelar</SecondaryButton>,
};

describe('acessibilidade dos componentes reutilizáveis', () => {
    test.each(Object.entries(cases))('%s', async (_, element) => {
        const { container } = render(element);

        expect(await axe(container)).toHaveNoViolations();
    });

    test('Dropdown aberto', async () => {
        const { container } = render(
            <Dropdown>
                <Dropdown.Trigger>
                    <button type="button">Conta</button>
                </Dropdown.Trigger>
                <Dropdown.Content>
                    <Dropdown.Link href="/perfil">Perfil</Dropdown.Link>
                </Dropdown.Content>
            </Dropdown>,
        );

        fireEvent.click(screen.getByRole('button', { name: 'Conta' }));

        expect(await axe(container)).toHaveNoViolations();
    });

    test('Modal aberto', async () => {
        render(
            <Modal show label="Confirmar exclusão" onClose={() => {}}>
                <h2>Confirmar exclusão</h2>
            </Modal>,
        );

        expect(await screen.findByRole('dialog')).toBeTruthy();
        expect(await axe(document.body)).toHaveNoViolations();
    });
});
