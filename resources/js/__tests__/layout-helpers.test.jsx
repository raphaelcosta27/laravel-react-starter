import PostLink from '@/Components/PostLink';
import TextLink from '@/Components/TextLink';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import GuestLayout from '@/Layouts/GuestLayout';
import { fieldProps } from '@/lib/form';
import DeleteUserForm from '@/Pages/Profile/Partials/DeleteUserForm';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm';
import { fireEvent, render, screen, within } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test, vi } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';

describe('fieldProps', () => {
    const form = (errors = {}) => ({
        data: { email: 'ana@example.com' },
        errors,
        setData: vi.fn(),
    });

    test('liga o campo ao formulário', () => {
        const current = form();
        const props = fieldProps(current, 'email');

        expect(props).toMatchObject({
            id: 'email',
            name: 'email',
            value: 'ana@example.com',
            error: false,
            fullWidth: true,
        });

        props.onChange({ target: { value: 'bia@example.com' } });

        expect(current.setData).toHaveBeenCalledWith('email', 'bia@example.com');
    });

    test('mostra o erro do campo', () => {
        const props = fieldProps(form({ email: 'Inválido' }), 'email');

        expect(props.error).toBe(true);
        expect(props.helperText).toBe('Inválido');
    });

    test('obrigatório só no campo nativo, sem asterisco no rótulo', () => {
        const props = fieldProps(form(), 'email', { required: true });

        expect(props.required).toBeUndefined();
        expect(props.slotProps.htmlInput.required).toBe(true);
    });
});

describe('links', () => {
    test('TextLink é um link', () => {
        render(<TextLink href="/entrar">Entrar</TextLink>);

        expect(
            screen.getByRole('link', { name: 'Entrar' }).getAttribute('href'),
        ).toBe('/entrar');
    });

    test('PostLink é um botão', () => {
        render(<PostLink href="/sair">Sair</PostLink>);

        expect(screen.getByRole('button', { name: 'Sair' })).toBeTruthy();
    });
});

describe('layouts', () => {
    beforeEach(() =>
        setPageProps({
            translations,
            auth: {
                user: { name: 'Ana', email: 'ana@example.com' },
                displayName: 'Ana',
                isAdministrator: true,
                permissions: [],
            },
        }),
    );

    test('o título da página é o cabeçalho principal', () => {
        render(<AuthenticatedLayout title="Usuários">conteúdo</AuthenticatedLayout>);

        expect(
            screen.getByRole('heading', { level: 1, name: 'Usuários' }),
        ).toBeTruthy();
        expect(screen.getByRole('main').textContent).toBe('conteúdo');
    });

    test('o menu da conta traz perfil e saída', async () => {
        render(<AuthenticatedLayout>conteúdo</AuthenticatedLayout>);

        fireEvent.click(screen.getByRole('button', { name: 'Olá, Ana' }));

        const menu = await screen.findByRole('menu');

        expect(within(menu).getByRole('menuitem', { name: 'Perfil' })).toBeTruthy();
        expect(within(menu).getByRole('menuitem', { name: 'Sair' })).toBeTruthy();
    });

    test('o menu de navegação abre pelo botão', async () => {
        render(<AuthenticatedLayout>conteúdo</AuthenticatedLayout>);

        const button = screen.getByRole('button', { name: 'Menu de navegação' });

        expect(button.getAttribute('aria-expanded')).toBe('false');

        fireEvent.click(button);

        expect(await screen.findByText('ana@example.com')).toBeTruthy();
        expect(
            screen.getByRole('dialog', { name: 'Menu de navegação' }),
        ).toBeTruthy();
        expect(
            screen
                .getByRole('button', { name: 'Menu de navegação', hidden: true })
                .getAttribute('aria-expanded'),
        ).toBe('true');
    });

    test('a área atual é indicada ao leitor de tela', () => {
        globalThis.route = Object.assign(
            (name) =>
                name === undefined
                    ? { current: (pattern) => pattern === 'admin.users.*' }
                    : `/${name}`,
        );

        render(<AuthenticatedLayout>conteúdo</AuthenticatedLayout>);

        const [link] = screen.getAllByRole('link', {
            name: 'Usuários',
            hidden: true,
        });

        expect(link.getAttribute('aria-current')).toBe('page');

        globalThis.route = (name) =>
            name === undefined ? { current: () => false } : `/${name}`;
    });

    test('o diálogo de exclusão da conta tem nome e não repete identificadores', async () => {
        render(
            <>
                <UpdatePasswordForm />
                <DeleteUserForm />
            </>,
        );

        fireEvent.click(screen.getByRole('button', { name: 'Excluir conta' }));

        expect(
            await screen.findByRole('dialog', { name: /Tem certeza/ }),
        ).toBeTruthy();

        const ids = [...document.querySelectorAll('[id]')].map(
            (element) => element.id,
        );

        expect(ids.filter((id, index) => ids.indexOf(id) !== index)).toEqual([]);
    });

    test('GuestLayout não tem violações', async () => {
        const { container } = render(<GuestLayout>conteúdo</GuestLayout>);

        expect(screen.getByRole('main').textContent).toBe('conteúdo');
        expect(await axe(container)).toHaveNoViolations();
    });
});
