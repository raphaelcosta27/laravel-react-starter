import { useTranslations } from '@/lib/i18n';
import CloseIcon from '@mui/icons-material/Close';
import CheckIcon from '@mui/icons-material/Check';
import DeleteOutlinedIcon from '@mui/icons-material/DeleteOutlined';
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
    confirmIcon,
    danger = false,
    processing = false,
    onConfirm,
    onClose,
    onExited,
    children,
}) {
    const __ = useTranslations();
    const titleId = useId();
    const ConfirmIcon = confirmIcon ?? (danger ? DeleteOutlinedIcon : CheckIcon);

    return (
        <Dialog
            open={show}
            onClose={onClose}
            slotProps={{ transition: { onExited } }}
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
                <Button startIcon={<CloseIcon />} autoFocus onClick={onClose}>
                    {__('Cancel')}
                </Button>

                <Button
                    startIcon={<CheckIcon />}
                    variant="contained"
                    color={danger ? 'error' : 'primary'}
                    startIcon={<ConfirmIcon />}
                    disabled={processing}
                    onClick={onConfirm}
                >
                    {confirmLabel ?? __('Confirm')}
                </Button>
            </DialogActions>
        </Dialog>
    );
}
