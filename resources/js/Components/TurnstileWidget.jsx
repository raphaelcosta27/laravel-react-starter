import { useTranslations } from '@/lib/i18n';
import Alert from '@mui/material/Alert';
import Box from '@mui/material/Box';
import { useEffect, useRef, useState } from 'react';

const SCRIPT_URL =
    'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';

let loading = null;

function loadTurnstile() {
    if (window.turnstile) {
        return Promise.resolve();
    }

    loading ??= new Promise((resolve, reject) => {
        const script = document.createElement('script');

        script.src = SCRIPT_URL;
        script.async = true;
        script.onload = resolve;
        script.onerror = () => {
            loading = null;
            script.remove();
            reject(new Error('Turnstile indisponível'));
        };

        document.head.appendChild(script);
    });

    return loading;
}

/**
 * Captcha do Cloudflare Turnstile. A prova vale uma vez: mude `resetKey` depois
 * de cada envio recusado, para o widget gerar outra.
 */
export default function TurnstileWidget({ siteKey, onChange, resetKey = 0 }) {
    const __ = useTranslations();
    const container = useRef(null);
    const change = useRef(onChange);
    const [failed, setFailed] = useState(false);

    useEffect(() => {
        change.current = onChange;
    });

    useEffect(() => {
        let active = true;
        let widget;

        change.current('');
        setFailed(false);

        loadTurnstile()
            .then(() => {
                if (!active) {
                    return;
                }

                widget = window.turnstile.render(container.current, {
                    sitekey: siteKey,
                    callback: (token) => change.current(token),
                    'expired-callback': () => change.current(''),
                    'error-callback': () => {
                        change.current('');
                        setFailed(true);
                    },
                });
            })
            .catch(() => active && setFailed(true));

        return () => {
            active = false;

            if (widget !== undefined) {
                window.turnstile?.remove(widget);
            }
        };
    }, [siteKey, resetKey]);

    return (
        <>
            <Box ref={container} />

            {failed && (
                <Alert severity="error">
                    {__('The verification service is unavailable. Try again later.')}
                </Alert>
            )}
        </>
    );
}
