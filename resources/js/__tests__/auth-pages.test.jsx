import ConfirmPassword from '@/Pages/Auth/ConfirmPassword';
import ForgotPassword from '@/Pages/Auth/ForgotPassword';
import Login from '@/Pages/Auth/Login';
import Register from '@/Pages/Auth/Register';
import RequestAccess from '@/Pages/Auth/RequestAccess';
import ResetPassword from '@/Pages/Auth/ResetPassword';
import VerifyEmail from '@/Pages/Auth/VerifyEmail';
import { render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';
import { ENGLISH_WORDS } from './helpers/translations';

const pages = {
    Login: <Login canResetPassword canRegister />,
    ForgotPassword: <ForgotPassword />,
    ResetPassword: <ResetPassword token="abc" email="ana@example.com" />,
    VerifyEmail: <VerifyEmail />,
    ConfirmPassword: <ConfirmPassword />,
    Register: <Register />,
    RequestAccess: <RequestAccess />,
};

describe('telas de autenticação', () => {
    beforeEach(() => setPageProps({ translations }));

    test.each(Object.entries(pages))('%s', async (_, page) => {
        const { container } = render(page);

        expect(screen.getByRole('main')).toBeTruthy();
        expect(screen.getByRole('heading', { level: 1 })).toBeTruthy();
        expect(container.textContent).not.toMatch(ENGLISH_WORDS);
        expect(await axe(container)).toHaveNoViolations();
    });

    test('Login sem chave não mostra captcha', () => {
        render(<Login canResetPassword={false} canRegister={false} captchaSiteKey={null} />);

        expect(document.head.querySelector('script[src*="turnstile"]')).toBeNull();
    });

    test('Login com chave carrega o captcha e anuncia a recusa', () => {
        setPageProps({ translations, errors: { captcha: 'Conclua a verificação humana.' } });

        render(<Login canResetPassword={false} canRegister={false} captchaSiteKey="chave" />);

        expect(document.head.querySelector('script[src*="turnstile"]')).toBeTruthy();
        expect(screen.getByRole('alert').textContent).toContain('Conclua a verificação humana.');

        document.head.querySelector('script[src*="turnstile"]').remove();
    });

    test('Login oferece nova autenticação', () => {
        render(<Login canResetPassword={false} canRegister={false} />);

        expect(
            screen
                .getByRole('link', { name: translations['Request new access'] })
                .getAttribute('href'),
        ).toBe('/access.request');
    });

    test('Login oculta o registro quando desativado', () => {
        render(<Login canResetPassword={false} canRegister={false} />);

        expect(
            screen.queryByRole('link', { name: translations.Register }),
        ).toBeNull();
    });

    test('VerifyEmail informa link expirado', () => {
        render(<VerifyEmail status="verification-link-expired" />);

        expect(
            screen.getByText(
                translations[
                    'This link has expired. Request a new one below.'
                ],
            ),
        ).toBeTruthy();
    });
});
