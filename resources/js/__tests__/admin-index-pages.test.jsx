import PermissionsIndex from '@/Pages/Admin/Permissions/Index';
import ProfilesIndex from '@/Pages/Admin/Profiles/Index';
import UsersIndex from '@/Pages/Admin/Users/Index';
import { render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';
import { ENGLISH_WORDS } from './helpers/translations';

const meta = { currentPage: 1, lastPage: 1, perPage: 25, total: 1 };
const query = { sort: 'name', search: '', filter: {}, tab: null, per_page: 25 };
const abilities = { create: true, update: true, delete: true, restore: true, export: true, bulk: [] };
const can = { update: false, delete: true, restore: false, status: true };

const pages = {
    Perfis: [
        ProfilesIndex,
        { id: 1, name: 'Financeiro', description: 'Equipe', users_count: 2, can },
        'Financeiro',
    ],
    Permissões: [
        PermissionsIndex,
        { id: 1, title: 'Gerar', code: 'gerar', description: 'Permite', can },
        'gerar',
    ],
    Usuários: [
        UsersIndex,
        {
            id: 1,
            name: 'Ana',
            email: 'ana@example.com',
            profile_name: 'Financeiro',
            is_active: true,
            confirmed: true,
            can,
        },
        'ana@example.com',
    ],
};

describe('listagens do painel', () => {
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

    test.each(Object.entries(pages))('%s', async (title, [Page, row, text]) => {
        const { container } = render(
            <Page
                rows={{ data: [row], meta }}
                query={query}
                tabs={[]}
                summaries={{}}
                abilities={abilities}
                profiles={[]}
            />,
        );

        expect(screen.getByRole('heading', { level: 1, name: title })).toBeTruthy();
        expect(screen.getByRole('table', { name: title })).toBeTruthy();
        expect(screen.getByRole('cell', { name: text })).toBeTruthy();
        expect(container.textContent).not.toMatch(ENGLISH_WORDS);
        expect(await axe(container)).toHaveNoViolations();
    });
});
