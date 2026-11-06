import ActionIconButton from '@/Components/ActionIconButton';
import ConfirmDialog from '@/Components/ConfirmDialog';
import { useTranslations } from '@/lib/i18n';
import { useBackClose } from '@/lib/useBackClose';
import ArrowBackIcon from '@mui/icons-material/ArrowBack';
import CloseIcon from '@mui/icons-material/Close';
import SaveOutlinedIcon from '@mui/icons-material/SaveOutlined';
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

    useBackClose(open, close);

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
                <DialogTitle
                    sx={{ alignItems: 'center', display: 'flex', gap: 1 }}
                >
                    {fullScreen && (
                        <ActionIconButton
                            icon={ArrowBackIcon}
                            label={__('Back')}
                            disabled={processing}
                            onClick={close}
                        />
                    )}

                    <span id={titleId}>{title}</span>
                </DialogTitle>

                <DialogContent dividers>
                    <Box sx={{ display: 'grid', gap: 2 }}>{children}</Box>
                </DialogContent>

                <DialogActions>
                    <Button
                        startIcon={<CloseIcon />}
                        disabled={processing}
                        onClick={close}
                    >
                        {__('Cancel')}
                    </Button>

                    {!hideSubmit && (
                        <Button
                            type="submit"
                            variant="contained"
                            startIcon={<SaveOutlinedIcon />}
                            disabled={processing}
                        >
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
