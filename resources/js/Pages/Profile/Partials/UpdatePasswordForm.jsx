import PasswordStrength from '@/Components/PasswordStrength';
import { fieldProps } from '@/lib/form';
import { useTranslations } from '@/lib/i18n';
import { useForm } from '@inertiajs/react';
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';
import TextField from '@mui/material/TextField';
import Typography from '@mui/material/Typography';
import { useRef } from 'react';

export default function UpdatePasswordForm() {
    const __ = useTranslations();
    const passwordInput = useRef();
    const currentPasswordInput = useRef();

    const form = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const updatePassword = (event) => {
        event.preventDefault();

        form.put(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
            onError: (errors) => {
                if (errors.password) {
                    form.reset('password', 'password_confirmation');
                    passwordInput.current.focus();
                }

                if (errors.current_password) {
                    form.reset('current_password');
                    currentPasswordInput.current.focus();
                }
            },
        });
    };

    return (
        <Stack component="section" spacing={3} sx={{ maxWidth: 576 }}>
            <header>
                <Typography component="h2" variant="h6">
                    {__('Update Password')}
                </Typography>

                <Typography variant="body2" color="text.secondary">
                    {__('Ensure your account is using a long, random password to stay secure.')}
                </Typography>
            </header>

            <Stack component="form" spacing={3} onSubmit={updatePassword}>
                <TextField
                    {...fieldProps(form, 'current_password')}
                    inputRef={currentPasswordInput}
                    type="password"
                    label={__('Current Password')}
                    autoComplete="current-password"
                />

                <TextField
                    {...fieldProps(form, 'password')}
                    inputRef={passwordInput}
                    type="password"
                    label={__('New Password')}
                    autoComplete="new-password"
                />

                <PasswordStrength value={form.data.password} />

                <TextField
                    {...fieldProps(form, 'password_confirmation')}
                    type="password"
                    label={__('Confirm Password')}
                    autoComplete="new-password"
                />

                <Stack direction="row" spacing={2} sx={{ alignItems: 'center' }}>
                    <Button
                        type="submit"
                        variant="contained"
                        disabled={form.processing}
                    >
                        {__('Save')}
                    </Button>

                    {form.recentlySuccessful && (
                        <Typography variant="body2" color="text.secondary">
                            {__('Saved.')}
                        </Typography>
                    )}
                </Stack>
            </Stack>
        </Stack>
    );
}
