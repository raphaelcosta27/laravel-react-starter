import TextLink from '@/Components/TextLink';
import TurnstileWidget from '@/Components/TurnstileWidget';
import GuestLayout from '@/Layouts/GuestLayout';
import { fieldProps } from '@/lib/form';
import { useTranslations } from '@/lib/i18n';
import { Head, useForm } from '@inertiajs/react';
import LoginIcon from '@mui/icons-material/Login';
import Alert from '@mui/material/Alert';
import Button from '@mui/material/Button';
import Checkbox from '@mui/material/Checkbox';
import FormHelperText from '@mui/material/FormHelperText';
import FormControlLabel from '@mui/material/FormControlLabel';
import Stack from '@mui/material/Stack';
import TextField from '@mui/material/TextField';
import Typography from '@mui/material/Typography';
import { useState } from 'react';

export default function Login({
    status,
    canResetPassword,
    canRegister,
    captchaSiteKey,
}) {
    const __ = useTranslations();
    const [captchaReset, setCaptchaReset] = useState(0);
    const form = useForm({
        email: '',
        password: '',
        remember: false,
        captcha: '',
    });

    const submit = (event) => {
        event.preventDefault();

        form.post(route('login'), {
            onError: () => setCaptchaReset((current) => current + 1),
            onFinish: () => form.reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title={__('Log in')} />

            <Stack spacing={2}>
                <Typography component="h1" variant="h6">
                    {__('Log in')}
                </Typography>

                {status && (
                    <Alert severity="success" role="status">
                        {status}
                    </Alert>
                )}

                <Stack component="form" spacing={2} onSubmit={submit}>
                    <TextField
                        {...fieldProps(form, 'email', { kind: 'email' })}
                        type="email"
                        label={__('Email')}
                        autoComplete="username"
                        autoFocus
                    />

                    <TextField
                        {...fieldProps(form, 'password', { kind: 'password' })}
                        type="password"
                        label={__('Password')}
                        autoComplete="current-password"
                    />

                    <FormControlLabel
                        label={__('Remember me')}
                        control={
                            <Checkbox
                                name="remember"
                                checked={form.data.remember}
                                onChange={(event) =>
                                    form.setData('remember', event.target.checked)
                                }
                            />
                        }
                    />

                    {captchaSiteKey && (
                        <TurnstileWidget
                            siteKey={captchaSiteKey}
                            resetKey={captchaReset}
                            onChange={(token) => form.setData('captcha', token)}
                        />
                    )}

                    {form.errors.captcha && (
                        <FormHelperText error role="alert">
                            {form.errors.captcha}
                        </FormHelperText>
                    )}

                    <Stack
                        direction="row"
                        spacing={2}
                        sx={{ alignItems: 'center', justifyContent: 'flex-end' }}
                    >
                        {canResetPassword && (
                            <TextLink href={route('password.request')}>
                                {__('Forgot your password?')}
                            </TextLink>
                        )}

                        <Button
                            startIcon={<LoginIcon />}
                            type="submit"
                            variant="contained"
                            disabled={form.processing}
                        >
                            {__('Log in')}
                        </Button>
                    </Stack>
                </Stack>

                <Stack spacing={1}>
                    <TextLink href={route('access.request')}>
                        {__('Request new access')}
                    </TextLink>

                    {canRegister && (
                        <TextLink href={route('register')}>
                            {__('Register')}
                        </TextLink>
                    )}
                </Stack>
            </Stack>
        </GuestLayout>
    );
}
