import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { useTranslations } from '@/lib/i18n';
import { useEffect, useRef } from 'react';

export default function ConfirmDialog({
    show,
    title,
    message,
    confirmLabel,
    danger = false,
    processing = false,
    onConfirm,
    onClose,
    children,
}) {
    const __ = useTranslations();
    const panel = useRef(null);
    const ConfirmButton = danger ? DangerButton : PrimaryButton;

    /**
     * Um menu que acabou de fechar devolve o foco ao próprio botão depois que o
     * diálogo abriu. Quem usa o teclado precisa continuar dentro do diálogo.
     */
    useEffect(() => {
        if (!show) {
            return undefined;
        }

        const keepFocusInside = () => {
            if (panel.current && !panel.current.contains(document.activeElement)) {
                panel.current.querySelector('button')?.focus();
            }
        };

        const timer = setTimeout(keepFocusInside, 100);

        document.addEventListener('focusin', keepFocusInside);

        return () => {
            clearTimeout(timer);
            document.removeEventListener('focusin', keepFocusInside);
        };
    }, [show]);

    return (
        <Modal show={show} label={title} maxWidth="md" onClose={onClose}>
            <div ref={panel} className="p-6">
                <h2 className="text-lg font-medium text-gray-900">{title}</h2>

                {message && (
                    <p className="mt-2 text-sm text-gray-600">{message}</p>
                )}

                {children}

                <div className="mt-6 flex justify-end gap-3">
                    <SecondaryButton onClick={onClose}>
                        {__('Cancel')}
                    </SecondaryButton>

                    <ConfirmButton
                        type="button"
                        disabled={processing}
                        onClick={onConfirm}
                    >
                        {confirmLabel ?? __('Confirm')}
                    </ConfirmButton>
                </div>
            </div>
        </Modal>
    );
}
