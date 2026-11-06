import FieldIcon from '@/Components/FieldIcon';
import { fieldProps } from '@/lib/form';
import { useTranslations } from '@/lib/i18n';
import { useForm } from '@inertiajs/react';
import LockOutlinedIcon from '@mui/icons-material/LockOutlined';
import Button from '@mui/material/Button';
import Dialog from '@mui/material/Dialog';
import DialogActions from '@mui/material/DialogActions';
import DialogContent from '@mui/material/DialogContent';
import DialogContentText from '@mui/material/DialogContentText';
import DialogTitle from '@mui/material/DialogTitle';
import Stack from '@mui/material/Stack';
import TextField from '@mui/material/TextField';
import Typography from '@mui/material/Typography';
import { useId, useRef, useState } from 'react';

export default function DeleteUserForm() {
    const __ = useTranslations();
    const [confirming, setConfirming] = useState(false);
    const passwordInput = useRef();
    const titleId = useId();

    const form = useForm({ password: '' });

    const closeModal = () => {
        setConfirming(false);

        form.clearErrors();
        form.reset();
    };

    const deleteUser = (event) => {
        event.preventDefault();

        form.delete(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => passwordInput.current.focus(),
            onFinish: () => form.reset(),
        });
    };

    return (
        <Stack
            component="section"
            spacing={3}
            sx={{ alignItems: 'flex-start', maxWidth: 576 }}
        >
            <header>
                <Typography component="h2" variant="h6">
                    {__('Delete Account')}
                </Typography>

                <Typography variant="body2" color="text.secondary">
                    {__('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.')}
                </Typography>
            </header>

            <Button
                variant="contained"
                color="error"
                onClick={() => setConfirming(true)}
            >
                {__('Delete Account')}
            </Button>

            <Dialog
                open={confirming}
                aria-labelledby={titleId}
                maxWidth="sm"
                fullWidth
                onClose={closeModal}
            >
                <form onSubmit={deleteUser}>
                    <DialogTitle id={titleId}>
                        {__('Are you sure you want to delete your account?')}
                    </DialogTitle>

                    <DialogContent>
                        <DialogContentText sx={{ mb: 2 }}>
                            {__('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.')}
                        </DialogContentText>

                        <TextField
                            {...fieldProps(form, 'password', {
                                adornment: <FieldIcon icon={LockOutlinedIcon} />,
                            })}
                            id="delete-account-password"
                            inputRef={passwordInput}
                            type="password"
                            label={__('Password')}
                            autoFocus
                        />
                    </DialogContent>

                    <DialogActions>
                        <Button onClick={closeModal}>{__('Cancel')}</Button>

                        <Button
                            type="submit"
                            variant="contained"
                            color="error"
                            disabled={form.processing}
                        >
                            {__('Delete Account')}
                        </Button>
                    </DialogActions>
                </form>
            </Dialog>
        </Stack>
    );
}
