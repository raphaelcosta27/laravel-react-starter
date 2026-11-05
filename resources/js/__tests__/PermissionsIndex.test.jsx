import Index from '@/Pages/Admin/Permissions/Index';
import { router } from '@inertiajs/react';
import { fireEvent, render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { expectDescribedActions } from './helpers/actions';
import { setPageProps } from './helpers/inertia';

const meta = { currentPage: 1, lastPage: 1, perPage: 25, total: 1 };
const query = { sort: 'title', search: '', filter: {}, tab: null, per_page: 25 };

const row = (can) => ({
    id: 3,
    title: 'Gerar',
    code: 'gerar',
    description: 'Permite',
    can: { update: false, delete: false, restore: false, ...can },
});

const page = (can, abilities = {}) =>
    render(
        <Index
            rows={{ data: [row(can)], meta }}
            query={query}
            tabs={[]}
            summaries={{}}
            abilities={{ create: false, export: false, ...abilities }}
        />,
    );

describe('ações da listagem de permissões', () => {
    beforeEach(() =>
        setPageProps({
            translations,
            auth: {
                user: { id: 1, name: 'Ana', email: 'ana@example.com' },
                isAdministrator: false,
                permissions: [],
            },
        }),
    );

    test('oferece nova permissão somente com a habilidade', () => {
        const { unmount } = page({}, { create: true });

        expect(screen.getByRole('link', { name: 'Nova permissão' })).toBeTruthy();

        unmount();
        page({});

        expect(screen.queryByRole('link', { name: 'Nova permissão' })).toBeNull();
    });

    test('toda ação tem ícone e descrição', () => {
        page({ update: true, delete: true, restore: true });

        expectDescribedActions();
    });

    test('exibe editar e excluir conforme o can do registro', () => {
        page({ update: true });

        expect(screen.getByRole('link', { name: 'Editar' })).toBeTruthy();
        expect(screen.queryByRole('button', { name: 'Excluir' })).toBeNull();
    });

    test('excluir pede confirmação antes de enviar', async () => {
        page({ delete: true });

        fireEvent.click(screen.getByRole('button', { name: 'Excluir' }));

        expect(router.delete).not.toHaveBeenCalled();
        expect(await screen.findByRole('dialog')).toBeTruthy();

        fireEvent.click(screen.getByRole('button', { name: 'Confirmar' }));

        expect(router.delete).toHaveBeenCalledOnce();
    });

    test('não tem violações de acessibilidade', async () => {
        const { container } = page({ update: true, delete: true }, { create: true });

        expect(await axe(container)).toHaveNoViolations();
    });
});
