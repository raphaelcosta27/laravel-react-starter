import Welcome from '@/Pages/Welcome';
import { render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { expect, test } from 'vitest';
import { setPageProps } from './helpers/inertia';

const props = (database) => ({
    canLogin: true,
    canRegister: false,
    status: { database, checkedAt: '2026-11-04T10:00:00+00:00' },
});

const translations = {
    'Database connected': 'Banco de dados conectado',
    'Database unavailable': 'Banco de dados indisponível',
    'Checked at': 'Verificado em',
    'Log in': 'Entrar',
};

test('exibe nome da aplicação e dados do backend', async () => {
    setPageProps({ appName: 'Minha Aplicação', translations });

    const { container } = render(<Welcome {...props(true)} />);

    expect(
        screen.getByRole('heading', { level: 1, name: 'Minha Aplicação' }),
    ).toBeTruthy();
    expect(screen.getByText('Banco de dados conectado')).toBeTruthy();
    expect(container.querySelector('time').getAttribute('datetime')).toBe(
        '2026-11-04T10:00:00+00:00',
    );
    expect(await axe(container)).toHaveNoViolations();
});

test('informa banco indisponível', async () => {
    setPageProps({ translations });

    const { container } = render(<Welcome {...props(false)} />);

    expect(screen.getByText('Banco de dados indisponível')).toBeTruthy();
    expect(await axe(container)).toHaveNoViolations();
});

test('oculta o registro quando desativado', () => {
    setPageProps({ translations });

    render(<Welcome {...props(true)} />);

    expect(screen.getByRole('link', { name: 'Entrar' })).toBeTruthy();
    expect(screen.queryByRole('link', { name: 'Register' })).toBeNull();
});
