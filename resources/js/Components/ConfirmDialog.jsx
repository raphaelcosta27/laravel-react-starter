import { useTranslations } from '@/lib/i18n';
import Button from '@mui/material/Button';
import Dialog from '@mui/material/Dialog';
import DialogActions from '@mui/material/DialogActions';
import DialogContent from '@mui/material/DialogContent';
import DialogContentText from '@mui/material/DialogContentText';
import DialogTitle from '@mui/material/DialogTitle';
import { useId } from 'react';

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
    const titleId = useId();

    return (
        <Dialog
            open={show}
            onClose={onClose}
            aria-labelledby={titleId}
            maxWidth="sm"
            fullWidth
        >
            <DialogTitle id={titleId}>{title}</DialogTitle>

            {(message || children) && (
                <DialogContent>
                    {message && (
                        <DialogContentText>{message}</DialogContentText>
                    )}

                    {children}
                </DialogContent>
            )}

            <DialogActions>
                <Button autoFocus onClick={onClose}>
                    {__('Cancel')}
                </Button>

                <Button
                    variant="contained"
                    color={danger ? 'error' : 'primary'}
                    disabled={processing}
                    onClick={onConfirm}
                >
                    {confirmLabel ?? __('Confirm')}
                </Button>
            </DialogActions>
        </Dialog>
    );
}
