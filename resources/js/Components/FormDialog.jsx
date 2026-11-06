import ConfirmDialog from '@/Components/ConfirmDialog';
import { useTranslations } from '@/lib/i18n';
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Dialog from '@mui/material/Dialog';
import DialogActions from '@mui/material/DialogActions';
import DialogContent from '@mui/material/DialogContent';
import DialogTitle from '@mui/material/DialogTitle';
import { useTheme } from '@mui/material/styles';
import useMediaQuery from '@mui/material/useMediaQuery';
import { useId, useState } from 'react';

export default function FormDialog({
    open,
    title,
    onClose,
    onSubmit,
    processing = false,
    isDirty = false,
    submitLabel,
    hideSubmit = false,
    children,
}) {
    const __ = useTranslations();
    const theme = useTheme();
    const fullScreen = useMediaQuery(theme.breakpoints.down('sm'));
    const titleId = useId();
    const [discarding, setDiscarding] = useState(false);

    const close = () => {
        if (processing) {
            return;
        }

        if (isDirty) {
            setDiscarding(true);

            return;
        }

        onClose();
    };

    const discard = () => {
        setDiscarding(false);
        onClose();
    };

    const submit = (event) => {
        event.preventDefault();
        onSubmit();
    };

    return (
        <Dialog
            open={open}
            onClose={close}
            aria-labelledby={titleId}
            fullScreen={fullScreen}
            fullWidth
            maxWidth="sm"
        >
            <Box
                component="form"
                onSubmit={submit}
                sx={{
                    display: 'flex',
                    flexDirection: 'column',
                    height: fullScreen ? '100%' : 'auto',
                    minHeight: 0,
                }}
            >
                <DialogTitle id={titleId}>{title}</DialogTitle>

                <DialogContent dividers>
                    <Box sx={{ display: 'grid', gap: 2 }}>{children}</Box>
                </DialogContent>

                <DialogActions>
                    <Button disabled={processing} onClick={close}>
                        {__('Cancel')}
                    </Button>

                    {!hideSubmit && (
                        <Button type="submit" variant="contained" disabled={processing}>
                            {submitLabel ?? __('Save')}
                        </Button>
                    )}
                </DialogActions>
            </Box>

            <ConfirmDialog
                show={discarding}
                title={__('Discard changes')}
                message={__('The changes in this form will be lost.')}
                confirmLabel={__('Discard')}
                danger
                onConfirm={discard}
                onClose={() => setDiscarding(false)}
            />
        </Dialog>
    );
}
