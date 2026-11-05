import Dashboard from '@/Pages/Dashboard';
import Edit from '@/Pages/Profile/Edit';
import { render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { readdirSync, readFileSync } from 'node:fs';
import { join } from 'node:path';
import { beforeEach, describe, expect, test } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';
import { ENGLISH_WORDS } from './helpers/translations';

const pages = {
    Dashboard: <Dashboard />,
    'Profile/Edit': <Edit mustVerifyEmail={false} />,
};

describe('telas da área autenticada', () => {
    beforeEach(() =>
        setPageProps({
            translations,
            auth: { user: { name: 'Ana', email: 'ana@example.com' } },
        }),
    );

    test.each(Object.entries(pages))('%s', async (_, page) => {
        const { container } = render(page);

        expect(screen.getByRole('main')).toBeTruthy();
        expect(screen.getByRole('heading', { level: 1 })).toBeTruthy();
        expect(container.textContent).not.toMatch(ENGLISH_WORDS);
        expect(await axe(container)).toHaveNoViolations();
    });
});

test('toda chave usada no frontend tem tradução', () => {
    const root = join(process.cwd(), 'resources/js');
    const files = readdirSync(root, { recursive: true })
        .filter((file) => file.endsWith('.jsx'))
        .filter((file) => !file.includes('__tests__'));

    const missing = files.flatMap((file) =>
        [
            ...readFileSync(join(root, file), 'utf8').matchAll(
                /__\(\s*(['"])(.+?)\1\s*[,)]/gs,
            ),
        ]
            .map((match) => match[2])
            .filter((key) => !(key in translations)),
    );

    expect(missing).toEqual([]);
});
