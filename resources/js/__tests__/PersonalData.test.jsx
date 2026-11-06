import { formatCpf, isValidCpf } from '@/lib/cpf';
import Edit from '@/Pages/Profile/Edit';
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm';
import { fireEvent, render, screen, within } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test } from 'vitest';
import { setPageProps } from './helpers/inertia';

const genders = [
    { value: 'Female', label: 'Feminino' },
    { value: 'Prefer not to say', label: 'Prefiro não informar' },
    { value: 'Other', label: 'Outros' },
];

const auth = {
    user: { id: 1, name: 'Ana Lima', email: 'ana@example.com', email_verified_at: 'x' },
    displayName: 'Ana Lima',
    avatarUrl: null,
    permissions: [],
};

const renderForm = (props = {}) =>
    render(<UpdateProfileInformationForm genders={genders} {...props} />);

describe('formatCpf', () => {
    test.each([
        ['', ''],
        ['5', '5'],
        ['529', '529'],
        ['5299', '529.9'],
        ['529982', '529.982'],
        ['5299822', '529.982.2'],
        ['529982247', '529.982.247'],
        ['5299822472', '529.982.247-2'],
        ['52998224725', '529.982.247-25'],
        ['529.982.247-25', '529.982.247-25'],
        ['529982247259999', '529.982.247-25'],
        ['abc529', '529'],
    ])('formata "%s"', (value, expected) => {
        expect(formatCpf(value)).toBe(expected);
    });

    test('aceita valor vazio vindo do servidor', () => {
        expect(formatCpf(null)).toBe('');
    });
});

describe('isValidCpf', () => {
    test.each([
        ['52998224725', true],
        ['529.982.247-25', true],
        ['11144477735', true],
        ['52998224724', false],
        ['52998224715', false],
        ['11111111111', false],
        ['00000000000', false],
        ['5299822472', false],
        ['', false],
        [null, false],
    ])('"%s" é %s', (value, expected) => {
        expect(isValidCpf(value)).toBe(expected);
    });
});

describe('UpdateProfileInformationForm', () => {
    beforeEach(() => setPageProps({ auth }));

    const save = () => screen.getByRole('button', { name: 'Save' });

    test('CPF concluído e errado avisa na hora e bloqueia o salvar', () => {
        renderForm();

        const cpf = screen.getByLabelText('CPF');

        fireEvent.change(cpf, { target: { value: '52998224724' } });

        expect(cpf.getAttribute('aria-invalid')).toBe('true');
        expect(screen.getByRole('alert').textContent).toBe('Enter a valid CPF.');
        expect(save().disabled).toBe(true);
    });

    test('corrigir o CPF libera o salvar e tira o aviso', () => {
        renderForm();

        const cpf = screen.getByLabelText('CPF');

        fireEvent.change(cpf, { target: { value: '52998224724' } });
        fireEvent.change(cpf, { target: { value: '52998224725' } });

        expect(cpf.getAttribute('aria-invalid')).toBe('false');
        expect(screen.queryByText('Enter a valid CPF.')).toBeNull();
        expect(save().disabled).toBe(false);
    });

    test('CPF pela metade bloqueia, mas só avisa ao sair do campo', () => {
        renderForm();

        const cpf = screen.getByLabelText('CPF');

        fireEvent.change(cpf, { target: { value: '5299' } });

        expect(cpf.getAttribute('aria-invalid')).toBe('false');
        expect(save().disabled).toBe(true);

        fireEvent.blur(cpf);

        expect(cpf.getAttribute('aria-invalid')).toBe('true');
    });

    test('CPF vazio não bloqueia quando não é obrigatório', () => {
        renderForm();

        const cpf = screen.getByLabelText('CPF');

        fireEvent.change(cpf, { target: { value: '5299' } });
        fireEvent.change(cpf, { target: { value: '' } });
        fireEvent.blur(cpf);

        expect(cpf.getAttribute('aria-invalid')).toBe('false');
        expect(save().disabled).toBe(false);
    });

    test('mostra o nome da conta no nome completo', () => {
        renderForm();

        expect(screen.getByLabelText(/Full name/).value).toBe('Ana Lima');
        expect(screen.getByLabelText(/Full name/).required).toBe(true);
    });

    test('mostra os dados salvos, com o CPF mascarado', () => {
        renderForm({
            personalData: {
                preferred_name: 'Aninha',
                birth_date: '1990-05-20',
                gender: 'Female',
                cpf: '52998224725',
            },
        });

        expect(screen.getByLabelText('How should we call you?').value).toBe('Aninha');
        expect(screen.getByLabelText('Date of birth').value).toBe('1990-05-20');
        expect(screen.getByLabelText('Gender').value).toBe('Female');
        expect(screen.getByLabelText('CPF').value).toBe('529.982.247-25');
    });

    test('aplica a máscara enquanto a pessoa digita', () => {
        renderForm();

        const cpf = screen.getByLabelText('CPF');

        fireEvent.change(cpf, { target: { value: '5299822' } });
        expect(cpf.value).toBe('529.982.2');

        fireEvent.change(cpf, { target: { value: '52998224725' } });
        expect(cpf.value).toBe('529.982.247-25');
    });

    test('lista os gêneros recebidos, com "Outros" por último', () => {
        renderForm();

        const options = within(screen.getByLabelText('Gender'))
            .getAllByRole('option')
            .map((option) => option.textContent);

        expect(options).toEqual([
            'Select',
            'Feminino',
            'Prefiro não informar',
            'Outros',
        ]);
    });

    test('um gênero retirado da configuração volta ao vazio', () => {
        renderForm({ personalData: { gender: 'Removido' } });

        expect(screen.getByLabelText('Gender').value).toBe('');
    });

    test('o CPF só é obrigatório quando a configuração exige', () => {
        const { unmount } = renderForm();
        expect(screen.getByLabelText('CPF').required).toBe(false);
        unmount();

        renderForm({ cpfRequired: true });
        expect(screen.getByLabelText(/CPF/).required).toBe(true);
    });

    test('mostra o erro junto ao campo', () => {
        setPageProps({ auth, errors: { cpf: 'CPF inválido.' } });
        renderForm();

        const cpf = screen.getByLabelText('CPF');

        expect(cpf.getAttribute('aria-invalid')).toBe('true');
        expect(screen.getByText('CPF inválido.')).toBeTruthy();
    });

    test('abre sem dados e sem violações', async () => {
        const { container } = renderForm();

        expect(screen.getByLabelText('How should we call you?').value).toBe('');
        expect(await axe(container)).toHaveNoViolations();
    });
});

describe('Profile/Edit', () => {
    test('monta a página completa sem violações', async () => {
        setPageProps({ auth });

        const { container } = render(
            <Edit genders={genders} avatarMaxKb={2048} personalData={{}} />,
        );

        expect(await axe(container)).toHaveNoViolations();
    });
});
