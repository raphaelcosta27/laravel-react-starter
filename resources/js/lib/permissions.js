import { usePage } from '@inertiajs/react';

/**
 * Serve somente para exibir ou ocultar elementos: quem protege é o servidor.
 */
export function useCan() {
    const { auth } = usePage().props;

    return (code) =>
        auth?.isAdministrator === true ||
        (auth?.permissions ?? []).includes(code);
}
