import { visuallyHidden as hiddenStyle } from '@/lib/visuallyHidden';
import { usePage } from '@inertiajs/react';
import Alert from '@mui/material/Alert';
import Box from '@mui/material/Box';
import Snackbar from '@mui/material/Snackbar';
import { useEffect, useState } from 'react';

const SEVERITIES = ['success', 'warning', 'error'];
const DURATION = 6000;

/**
 * Região sempre presente, para que tecnologias assistivas anunciem cada mudança.
 * Sem `message`, exibe `flash` ou o motivo de uma ação recusada pelo servidor.
 * O aviso visível flutua, para não deslocar o conteúdo. Erro só sai quando fechado.
 */
export default function StatusAnnouncer({ message, visuallyHidden = false }) {
    const { flash, errors } = usePage().props;
    const refusal = errors?.permission ?? errors?.profile ?? errors?.user;

    const text = message ?? refusal ?? flash?.message ?? '';
    const type = refusal ? 'error' : flash?.type;
    const severity = SEVERITIES.includes(type) ? type : 'success';

    const [notice, setNotice] = useState({ text: '', severity });
    const [open, setOpen] = useState(false);

    useEffect(() => {
        if (text) {
            setNotice({ text, severity });
        }

        setOpen(Boolean(text));
    }, [text, severity, flash]);

    return (
        <>
            <Box role="status" aria-live="polite" aria-atomic="true" sx={hiddenStyle}>
                {text}
            </Box>

            {!visuallyHidden && (
                <Snackbar
                    open={open}
                    autoHideDuration={notice.severity === 'error' ? null : DURATION}
                    anchorOrigin={{ horizontal: 'center', vertical: 'bottom' }}
                    sx={{ marginBlockEnd: 'env(safe-area-inset-bottom)' }}
                    onClose={(event, reason) => {
                        if (reason !== 'clickaway') {
                            setOpen(false);
                        }
                    }}
                >
                    <Alert
                        role="none"
                        aria-hidden="true"
                        severity={notice.severity}
                        variant="filled"
                        onClose={() => setOpen(false)}
                    >
                        {notice.text}
                    </Alert>
                </Snackbar>
            )}
        </>
    );
}
