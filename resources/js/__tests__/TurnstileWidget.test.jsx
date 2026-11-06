import TurnstileWidget from '@/Components/TurnstileWidget';
import { act, render, screen, waitFor } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';

describe('captcha do login', () => {
    let options;

    beforeEach(() => {
        setPageProps({ translations });

        window.turnstile = {
            render: vi.fn((_, received) => {
                options = received;

                return 'widget-1';
            }),
            remove: vi.fn(),
        };
    });

    afterEach(() => {
        delete window.turnstile;
        document.head.querySelectorAll('script').forEach((script) => script.remove());
    });

    test('desenha o widget com a chave pública', async () => {
        render(<TurnstileWidget siteKey="chave" onChange={vi.fn()} />);

        await waitFor(() => expect(window.turnstile.render).toHaveBeenCalled());

        expect(options.sitekey).toBe('chave');
    });

    test('entrega a prova e a retira quando vence', async () => {
        const onChange = vi.fn();

        render(<TurnstileWidget siteKey="chave" onChange={onChange} />);

        await waitFor(() => expect(window.turnstile.render).toHaveBeenCalled());

        act(() => options.callback('prova'));
        expect(onChange).toHaveBeenLastCalledWith('prova');

        act(() => options['expired-callback']());
        expect(onChange).toHaveBeenLastCalledWith('');
    });

    test('recria o widget quando a prova já foi usada', async () => {
        const onChange = vi.fn();
        const { rerender } = render(
            <TurnstileWidget siteKey="chave" onChange={onChange} resetKey={0} />,
        );

        await waitFor(() => expect(window.turnstile.render).toHaveBeenCalledTimes(1));

        rerender(<TurnstileWidget siteKey="chave" onChange={onChange} resetKey={1} />);

        await waitFor(() => expect(window.turnstile.render).toHaveBeenCalledTimes(2));

        expect(window.turnstile.remove).toHaveBeenCalledWith('widget-1');
        expect(onChange).toHaveBeenLastCalledWith('');
    });

    test('avisa quando o serviço falha', async () => {
        render(<TurnstileWidget siteKey="chave" onChange={vi.fn()} />);

        await waitFor(() => expect(window.turnstile.render).toHaveBeenCalled());

        act(() => options['error-callback']());

        expect(screen.getByRole('alert').textContent).toContain(
            'O serviço de verificação está indisponível.',
        );
    });

    test('avisa quando o script não carrega', async () => {
        delete window.turnstile;

        render(<TurnstileWidget siteKey="chave" onChange={vi.fn()} />);

        const script = document.head.querySelector(
            'script[src^="https://challenges.cloudflare.com/turnstile/"]',
        );

        act(() => {
            script.dispatchEvent(new Event('error'));
        });

        expect(await screen.findByRole('alert')).toBeTruthy();
    });
});
