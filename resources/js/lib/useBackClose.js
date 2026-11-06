import { useEffect, useRef } from 'react';

const MARK = 'backClose';

const active = [];

let skipping = 0;

const marked = () => window.history.state?.[MARK] === true;

/** Entrada extra no histórico: é ela que o voltar consome, e não a tela. */
const mark = () =>
    window.history.pushState(
        { ...window.history.state, [MARK]: true },
        '',
        window.location.href,
    );

/** Roda em captura, antes do Inertia, que trocaria de tela. */
const intercept = (event) => {
    if (skipping > 0) {
        skipping -= 1;
        event.stopImmediatePropagation();

        return;
    }

    const handler = active.at(-1);

    if (handler) {
        event.stopImmediatePropagation();
        mark();
        handler.current();
    }
};

if (typeof window !== 'undefined') {
    window.addEventListener('popstate', intercept, true);
}

/** Enquanto `enabled`, o voltar do aparelho chama `onBack` e mantém a tela. */
export function useBackClose(enabled, onBack) {
    const handler = useRef(onBack);

    useEffect(() => {
        handler.current = onBack;
    });

    useEffect(() => {
        if (!enabled) {
            return undefined;
        }

        if (!marked()) {
            mark();
        }

        active.push(handler);

        return () => {
            active.splice(active.indexOf(handler), 1);

            // Depois de salvar, a tela nova já está por cima: a entrada fica onde está.
            if (active.length === 0 && marked()) {
                skipping += 1;
                window.history.back();
            }
        };
    }, [enabled]);
}
