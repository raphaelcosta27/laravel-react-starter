import TextLink from '@/Components/TextLink';
import GuestLayout from '@/Layouts/GuestLayout';
import { fieldProps } from '@/lib/form';
import { useTranslations } from '@/lib/i18n';
import { Head, useForm } from '@inertiajs/react';
import Alert from '@mui/material/Alert';
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';
import TextField from '@mui/material/TextField';
import Typography from '@mui/material/Typography';

export default function LoginCode({ minutes, status }) {
    const __ = useTranslations();
    const form = useForm({ code: '' });

    const submit = (event) => {
        event.preventDefault();

        form.post(route('login.code'), {
            onFinish: () => form.reset('code'),
        });
    };

    // Pelo mesmo formulário: o erro do reenvio aparece no campo e limpa o anterior.
    const resend = () => {
        form.post(route('login.code.resend'), {
            preserveScroll: true,
            onFinish: () => form.reset('code'),
        });
    };

    return (
        <GuestLayout>
            <Head title={__('Verification code')} />

            <Stack spacing={2}>
                <Typography component="h1" variant="h6">
                    {__('Verification code')}
                </Typography>

                <Typography variant="body2">
                    {__(
                        'We sent a 6-digit code to your email. It is valid for :minutes minutes.',
                        { minutes },
                    )}
                </Typography>

                {status && (
                    <Alert severity="success" role="status">
                        {status}
                    </Alert>
                )}

                <Stack component="form" spacing={2} onSubmit={submit}>
                    <TextField
                        {...fieldProps(form, 'code', {
                            required: true,
                            htmlInput: { maxLength: 6, inputMode: 'numeric' },
                        })}
                        label={__('Code')}
                        autoComplete="one-time-code"
                        autoFocus
                    />

                    <Button
                        type="submit"
                        variant="contained"
                        disabled={form.processing}
                    >
                        {__('Verify')}
                    </Button>
                </Stack>

                <Button disabled={form.processing} onClick={resend}>
                    {__('Send another code')}
                </Button>

                <TextLink href={route('login')}>{__('Back to login')}</TextLink>
            </Stack>
        </GuestLayout>
    );
}
