import LoginCode from '@/Pages/Auth/LoginCode';
import { render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';

describe('tela de código do login', () => {
    beforeEach(() => setPageProps({ translations }));

    test('explica a etapa e não tem violações de acessibilidade', async () => {
        const { container } = render(<LoginCode minutes={15} />);

        expect(
            screen.getByRole('heading', { level: 1, name: 'Código de verificação' }),
        ).toBeTruthy();
        expect(screen.getByText(/válido por 15 minutos/)).toBeTruthy();
        expect(await axe(container)).toHaveNoViolations();
    });

    test('o campo aceita somente o código e o preenchimento automático', () => {
        render(<LoginCode minutes={15} />);

        const input = screen.getByLabelText(/^Código/);

        expect(input.required).toBe(true);
        expect(input.getAttribute('inputmode')).toBe('numeric');
        expect(input.getAttribute('autocomplete')).toBe('one-time-code');
        expect(input.getAttribute('maxlength')).toBe('6');
    });

    test('exibe o erro do código', () => {
        setPageProps({ translations, errors: { code: 'O código está incorreto.' } });

        render(<LoginCode minutes={15} />);

        expect(screen.getByText('O código está incorreto.')).toBeTruthy();
    });

    test('anuncia o reenvio', () => {
        render(<LoginCode minutes={15} status="Enviamos um novo código." />);

        expect(screen.getByRole('status').textContent).toContain('Enviamos um novo código.');
    });

    test('oferece outro código e a volta ao início do login', () => {
        render(<LoginCode minutes={15} />);

        expect(screen.getByRole('button', { name: 'Enviar outro código' })).toBeTruthy();
        expect(
            screen.getByRole('link', { name: 'Voltar ao login' }).getAttribute('href'),
        ).toBe('/login');
    });
});
