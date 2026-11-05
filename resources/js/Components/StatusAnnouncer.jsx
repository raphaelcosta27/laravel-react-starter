import { visuallyHidden as hiddenStyle } from '@/lib/visuallyHidden';
import { usePage } from '@inertiajs/react';
import Alert from '@mui/material/Alert';
import Box from '@mui/material/Box';

const SEVERITIES = ['success', 'warning', 'error'];

/**
 * Região sempre presente, para que tecnologias assistivas anunciem cada mudança.
 * Sem `message`, exibe `flash` ou o motivo de uma ação recusada pelo servidor.
 */
export default function StatusAnnouncer({ message, visuallyHidden = false }) {
    const { flash, errors } = usePage().props;
    const refusal = errors?.permission ?? errors?.profile ?? errors?.user;

    const text = message ?? refusal ?? flash?.message ?? '';
    const type = refusal ? 'error' : flash?.type;
    const severity = SEVERITIES.includes(type) ? type : 'success';
    const visible = text && !visuallyHidden;

    return (
        <Box
            role="status"
            aria-live="polite"
            aria-atomic="true"
            sx={
                visible
                    ? { maxWidth: 1536, mx: 'auto', mt: 2, px: { xs: 2, sm: 3 } }
                    : hiddenStyle
            }
        >
            {visible ? (
                <Alert severity={severity} role="none">
                    {text}
                </Alert>
            ) : (
                text
            )}
        </Box>
    );
}
