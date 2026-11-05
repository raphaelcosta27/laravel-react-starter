import Error from '@/Pages/Error';
import { render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { expect, test } from 'vitest';

test.each([403, 404, 419, 429, 500, 503])(
    'mostra mensagem e caminho de volta para %i',
    async (status) => {
        const { container } = render(<Error status={status} />);

        expect(screen.getByRole('heading', { level: 1 }).textContent).not.toBe(
            '',
        );
        expect(screen.getByText(String(status))).toBeTruthy();
        expect(screen.getByRole('link').getAttribute('href')).toBe('/');
        expect(await axe(container)).toHaveNoViolations();
    },
);

test('oferece saída quando indicado', () => {
    render(<Error status={403} canLogout />);

    expect(screen.getByRole('button', { name: 'Log Out' })).toBeTruthy();
});
