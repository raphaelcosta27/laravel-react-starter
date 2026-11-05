import ChangePassword from '@/Pages/Auth/ChangePassword';
import { render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { expect, test } from 'vitest';
import { setPageProps } from './helpers/inertia';

test('tem campos rotulados e opção de sair, sem violações', async () => {
    const { container } = render(<ChangePassword />);

    expect(screen.getByLabelText('Current Password')).toBeTruthy();
    expect(screen.getByLabelText('New Password')).toBeTruthy();
    expect(screen.getByLabelText('Confirm Password')).toBeTruthy();
    expect(screen.getByRole('button', { name: 'Log Out' })).toBeTruthy();
    expect(await axe(container)).toHaveNoViolations();
});

test('associa o erro ao campo', async () => {
    setPageProps({ errors: { password: 'Senha muito curta' } });

    const { container } = render(<ChangePassword />);
    const field = screen.getByLabelText('New Password');

    expect(field.getAttribute('aria-invalid')).toBe('true');
    expect(
        document.getElementById(field.getAttribute('aria-describedby'))
            .textContent,
    ).toBe('Senha muito curta');
    expect(await axe(container)).toHaveNoViolations();
});
