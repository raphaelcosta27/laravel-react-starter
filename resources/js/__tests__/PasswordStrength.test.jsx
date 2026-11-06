import PasswordStrength from '@/Components/PasswordStrength';
import ChangePassword from '@/Pages/Auth/ChangePassword';
import Register from '@/Pages/Auth/Register';
import ResetPassword from '@/Pages/Auth/ResetPassword';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm';
import { render, screen, within } from '@testing-library/react';
import { axe } from 'jest-axe';
import { describe, expect, test } from 'vitest';
import fixture from '../../../tests/fixtures/password-criteria.json';
import { visuallyHidden } from '@/lib/visuallyHidden';

const bar = () => screen.getByRole('progressbar');
const item = (label) => screen.getByText(label).closest('li');
const metKeys = () =>
    fixture.criteria
        .filter(({ label }) => within(item(label)).queryByText('Met'))
        .map(({ key }) => key);

describe('PasswordStrength', () => {
    test('começa zerada, com todos os critérios pendentes e sem violações', async () => {
        const { container } = render(<PasswordStrength value="" />);

        expect(bar().getAttribute('aria-valuenow')).toBe('0');
        expect(metKeys()).toEqual([]);
        expect(screen.getAllByText('Pending')).toHaveLength(5);
        expect(await axe(container)).toHaveNoViolations();
    });

    test.each(fixture.samples)(
        'marca os mesmos critérios do servidor para "$password"',
        ({ password, met }) => {
            render(<PasswordStrength value={password} />);

            expect(metKeys()).toEqual(met);
            expect(bar().getAttribute('aria-valuenow')).toBe(
                String(met.length * 20),
            );
        },
    );

    test('avança e recua conforme a digitação', () => {
        const { rerender } = render(<PasswordStrength value="a" />);
        expect(bar().getAttribute('aria-valuenow')).toBe('20');

        rerender(<PasswordStrength value="aA1" />);
        expect(bar().getAttribute('aria-valuenow')).toBe('60');

        rerender(<PasswordStrength value="aA" />);
        expect(bar().getAttribute('aria-valuenow')).toBe('40');
    });

    test('descreve a força em texto, sem depender da cor', () => {
        const { rerender } = render(<PasswordStrength value="a" />);
        expect(bar().getAttribute('aria-valuetext')).toBe('Weak');
        expect(screen.getByText('Password strength: Weak')).toBeTruthy();

        rerender(<PasswordStrength value="Abcdefg1" />);
        expect(bar().getAttribute('aria-valuetext')).toBe('Medium');

        rerender(<PasswordStrength value="Abcdefg1!" />);
        expect(bar().getAttribute('aria-valuetext')).toBe('Strong');
    });

    test('anuncia a força em uma região educada, somente com senha digitada', () => {
        const { rerender } = render(<PasswordStrength value="" />);
        const region = screen.getByRole('status');

        expect(region.getAttribute('aria-live')).toBe('polite');
        expect(region.textContent).toBe('');

        rerender(<PasswordStrength value="Abcdefg1!" />);

        expect(region.textContent).toBe(
            'Password strength: Strong. 5 of 5 criteria met.',
        );
    });

    test.each([
        ['cadastro', Register],
        ['redefinição', ResetPassword],
        ['troca obrigatória', ChangePassword],
        ['troca voluntária', UpdatePasswordForm],
    ])('aparece na tela de %s', (_, Page) => {
        render(<Page />);

        expect(bar()).toBeTruthy();
    });

    test('o texto oculto ocupa 1 pixel, sem alargar a página', () => {
        expect(visuallyHidden).toMatchObject({ width: '1px', height: '1px' });
    });

    test('não tem violações com a senha completa', async () => {
        const { container } = render(<PasswordStrength value="Abcdefg1!" />);

        expect(await axe(container)).toHaveNoViolations();
    });
});
