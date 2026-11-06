import PersonAvatar from '@/Components/PersonAvatar';
import UserAvatar, { initials } from '@/Components/UserAvatar';
import AppShell from '@/Layouts/AppShell';
import Dashboard from '@/Pages/Dashboard';
import UpdateAvatarForm from '@/Pages/Profile/Partials/UpdateAvatarForm';
import { fireEvent, render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test, vi } from 'vitest';
import { router, setPageProps } from './helpers/inertia';

const auth = {
    user: { id: 1, name: 'Ana Maria Lima', email: 'ana@example.com' },
    displayName: 'Aninha',
    avatarUrl: null,
    permissions: [],
};

const picture = (size = 10, type = 'image/png') => {
    const file = new File(['x'], 'foto.png', { type });
    Object.defineProperty(file, 'size', { value: size });

    return file;
};

const choose = (file) =>
    fireEvent.change(screen.getByLabelText('Profile picture file'), {
        target: { files: [file] },
    });

beforeEach(() => setPageProps({ auth }));

describe('initials', () => {
    test.each([
        ['Ana Maria Lima', 'AL'],
        ['Ana', 'A'],
        ['  ana   lima  ', 'AL'],
        ['Élida Souza', 'ÉS'],
        ['', ''],
    ])('de "%s"', (name, expected) => {
        expect(initials(name)).toBe(expected);
    });
});

describe('UserAvatar', () => {
    test('mostra as iniciais sem imagem', () => {
        render(<UserAvatar />);

        expect(screen.getByText('AL')).toBeTruthy();
        expect(document.querySelector('img')).toBeNull();
    });

    test('mostra a imagem quando existe', () => {
        setPageProps({ auth: { ...auth, avatarUrl: '/profile/avatar?v=1' } });
        render(<UserAvatar alt="Foto" />);

        expect(screen.getByAltText('Foto').getAttribute('src')).toBe(
            '/profile/avatar?v=1',
        );
    });

    test('não tem violações', async () => {
        const { container } = render(<UserAvatar />);

        expect(await axe(container)).toHaveNoViolations();
    });
});

describe('PersonAvatar', () => {
    test('a foto só carrega quando aparece na tela', () => {
        render(<PersonAvatar name="Ana Lima" src="/foto?v=1" alt="Foto" />);

        expect(screen.getByAltText('Foto').getAttribute('loading')).toBe('lazy');
    });

    test('sem foto, mostra as iniciais', () => {
        render(<PersonAvatar name="Ana Lima" />);

        expect(screen.getByText('AL')).toBeTruthy();
    });

    test('uma foto que falha vira iniciais', () => {
        render(<PersonAvatar name="Ana Lima" src="/foto?v=1" alt="Foto" />);

        fireEvent.error(screen.getByAltText('Foto'));

        expect(screen.getByText('AL')).toBeTruthy();
        expect(document.querySelector('img')).toBeNull();
    });

    test('a foto nova é tentada depois de uma falha', () => {
        const { rerender } = render(<PersonAvatar name="Ana" src="/foto?v=1" alt="Foto" />);

        fireEvent.error(screen.getByAltText('Foto'));
        rerender(<PersonAvatar name="Ana" src="/foto?v=2" alt="Foto" />);

        expect(screen.getByAltText('Foto').getAttribute('src')).toBe('/foto?v=2');
    });

    test('sem texto alternativo, é decorativa', () => {
        const { container } = render(<PersonAvatar name="Ana" src="/foto?v=1" />);

        expect(container.firstChild.getAttribute('aria-hidden')).toBe('true');
    });
});

describe('UpdateAvatarForm', () => {
    test('escolhe o arquivo por um botão real', () => {
        render(<UpdateAvatarForm maxKb={2048} />);

        const input = screen.getByLabelText('Profile picture file');
        const click = vi.spyOn(input, 'click');

        fireEvent.click(screen.getByRole('button', { name: 'Choose picture' }));

        expect(click).toHaveBeenCalled();
        expect(input.getAttribute('accept')).toBe('.jpg,.jpeg,.png,.webp');
    });

    test('envia a imagem escolhida', () => {
        render(<UpdateAvatarForm maxKb={2048} />);

        const file = picture();
        choose(file);

        expect(router.post).toHaveBeenCalledTimes(1);
        expect(router.post.mock.calls[0][0]).toBe('/profile.avatar.update');
        expect(router.post.mock.calls[0][1]).toEqual({ avatar: file });
    });

    test('mantém o que foi digitado nos outros formulários', () => {
        render(<UpdateAvatarForm maxKb={2048} />);

        choose(picture());

        expect(router.post.mock.calls[0][2].preserveState).toBe(true);
    });

    test('recusa antes do envio um arquivo acima do limite', () => {
        render(<UpdateAvatarForm maxKb={1024} />);

        choose(picture(1024 * 1024 + 1));

        expect(router.post).not.toHaveBeenCalled();
        expect(screen.getByRole('alert').textContent).toBe(
            'The picture must be at most 1 MB.',
        );
    });

    test('mostra o erro do servidor', () => {
        setPageProps({ auth, errors: { avatar: 'Formato não permitido.' } });
        render(<UpdateAvatarForm maxKb={2048} />);

        expect(screen.getByRole('alert').textContent).toBe('Formato não permitido.');
    });

    test('só oferece remover quando há imagem', () => {
        const { unmount } = render(<UpdateAvatarForm maxKb={2048} />);
        expect(screen.queryByRole('button', { name: 'Remove picture' })).toBeNull();
        unmount();

        setPageProps({ auth: { ...auth, avatarUrl: '/profile/avatar?v=1' } });
        render(<UpdateAvatarForm maxKb={2048} />);
        fireEvent.click(screen.getByRole('button', { name: 'Remove picture' }));

        expect(router.delete.mock.calls[0][0]).toBe('/profile.avatar.destroy');
    });

    test('não tem violações', async () => {
        const { container } = render(<UpdateAvatarForm maxKb={2048} />);

        expect(await axe(container)).toHaveNoViolations();
    });
});

describe('saudação', () => {
    test('o menu usa o nome preferido', () => {
        render(<AppShell>conteúdo</AppShell>);

        expect(screen.getByRole('button', { name: 'Hello, Aninha' })).toBeTruthy();
        expect(screen.getByText('Aninha')).toBeTruthy();
    });

    test('o painel inicial usa o nome preferido', () => {
        render(<Dashboard />);

        expect(screen.getByRole('heading', { name: 'Hello, Aninha' })).toBeTruthy();
    });
});
