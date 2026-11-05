import { usePage } from '@inertiajs/react';

const styles = {
    success: 'border-green-300 bg-green-50 text-green-900',
    warning: 'border-yellow-300 bg-yellow-50 text-yellow-900',
    error: 'border-red-300 bg-red-50 text-red-900',
};

/**
 * Região sempre presente, para que tecnologias assistivas anunciem cada mudança.
 * Sem `message`, exibe `flash` ou o motivo de uma ação recusada pelo servidor.
 */
export default function StatusAnnouncer({ message, visuallyHidden = false }) {
    const { flash, errors } = usePage().props;
    const refusal = errors?.permission ?? errors?.profile ?? errors?.user;

    const text = message ?? refusal ?? flash?.message ?? '';
    const style = styles[refusal ? 'error' : flash?.type] ?? styles.success;

    return (
        <div
            role="status"
            aria-live="polite"
            aria-atomic="true"
            className={
                text && !visuallyHidden
                    ? `mx-auto mt-4 max-w-7xl rounded-md border px-4 py-3 text-sm sm:mx-6 lg:mx-auto ${style}`
                    : 'sr-only'
            }
        >
            {text}
        </div>
    );
}
