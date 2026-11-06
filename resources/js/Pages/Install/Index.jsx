import EnvironmentForm, {
    environmentValues,
    focusFirstError,
} from '@/Components/EnvironmentForm';
import PasswordStrength from '@/Components/PasswordStrength';
import GuestLayout from '@/Layouts/GuestLayout';
import { fieldProps } from '@/lib/form';
import { useTranslations } from '@/lib/i18n';
import { Head, useForm } from '@inertiajs/react';
import ArrowForwardIcon from '@mui/icons-material/ArrowForward';
import StorageOutlinedIcon from '@mui/icons-material/StorageOutlined';
import CheckCircleOutlinedIcon from '@mui/icons-material/CheckCircleOutlined';
import Alert from '@mui/material/Alert';
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';
import TextField from '@mui/material/TextField';
import Typography from '@mui/material/Typography';

const STEPS = ['unlock', 'environment', 'database', 'user'];

function Unlock({ codePath }) {
    const __ = useTranslations();
    const form = useForm({ code: '' });

    const submit = (event) => {
        event.preventDefault();
        form.post(route('install.unlock'));
    };

    return (
        <Stack component="form" spacing={2} onSubmit={submit}>
            <Typography variant="body2" sx={{ overflowWrap: 'anywhere' }}>
                {__(
                    'Enter the installation code. It is in the file :path and in the application log, on the server.',
                    { path: codePath },
                )}
            </Typography>

            <TextField
                {...fieldProps(form, 'code', { kind: 'key', required: true })}
                label={__('Installation code')}
                autoComplete="off"
                autoFocus
            />

            <Typography variant="body2" color="text.secondary">
                {__(
                    'Lost the code? Run php artisan app:install-code to generate another.',
                )}
            </Typography>

            <Button startIcon={<ArrowForwardIcon />} type="submit" variant="contained" disabled={form.processing}>
                {__('Continue')}
            </Button>
        </Stack>
    );
}

function Environment({ groups, writable }) {
    const __ = useTranslations();
    const form = useForm({ values: environmentValues(groups) });

    const submit = (event) => {
        event.preventDefault();
        form.post(route('install.environment'), { onError: focusFirstError });
    };

    return (
        <Stack component="form" spacing={3} onSubmit={submit}>
            <Typography variant="body2">
                {__(
                    'Review the parameters. The database connection is tested before saving.',
                )}
            </Typography>

            {!writable && (
                <Alert severity="warning">
                    {__(
                        'The environment file cannot be written. Review its permissions on the server.',
                    )}
                </Alert>
            )}

            {form.errors.environment && (
                <Alert severity="error">{form.errors.environment}</Alert>
            )}

            <EnvironmentForm groups={groups} form={form} />

            <Button type="submit" variant="contained" startIcon={<StorageOutlinedIcon />} disabled={form.processing}>
                {__('Save and test connection')}
            </Button>
        </Stack>
    );
}

function Database() {
    const __ = useTranslations();
    const form = useForm({});

    const submit = (event) => {
        event.preventDefault();
        form.post(route('install.database'));
    };

    return (
        <Stack component="form" spacing={2} onSubmit={submit}>
            <Typography variant="body2">
                {__(
                    'The connection worked. Now the tables and the initial data will be created. This step can be repeated.',
                )}
            </Typography>

            {form.errors.database && (
                <Alert severity="error">{form.errors.database}</Alert>
            )}

            <Button type="submit" variant="contained" startIcon={<StorageOutlinedIcon />} disabled={form.processing}>
                {__('Prepare the database')}
            </Button>
        </Stack>
    );
}

function FirstUser() {
    const __ = useTranslations();
    const form = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (event) => {
        event.preventDefault();
        form.post(route('install.user'), {
            onError: () => form.reset('password', 'password_confirmation'),
        });
    };

    return (
        <Stack component="form" spacing={2} onSubmit={submit}>
            <Typography variant="body2">
                {__(
                    'This account will be an administrator, with the email already confirmed.',
                )}
            </Typography>

            <TextField
                {...fieldProps(form, 'name', { kind: 'person', required: true })}
                label={__('Name')}
                autoComplete="name"
                autoFocus
            />

            <TextField
                {...fieldProps(form, 'email', { kind: 'email', required: true })}
                type="email"
                label={__('Email')}
                autoComplete="username"
            />

            <TextField
                {...fieldProps(form, 'password', { kind: 'password', required: true })}
                type="password"
                label={__('Password')}
                autoComplete="new-password"
            />

            <PasswordStrength value={form.data.password} />

            <TextField
                {...fieldProps(form, 'password_confirmation', { kind: 'password', required: true })}
                type="password"
                label={__('Confirm Password')}
                autoComplete="new-password"
            />

            <Button type="submit" variant="contained" startIcon={<CheckCircleOutlinedIcon />} disabled={form.processing}>
                {__('Finish installation')}
            </Button>
        </Stack>
    );
}

export default function Index({ step, codePath, groups = [], writable = true }) {
    const __ = useTranslations();

    const titles = {
        unlock: __('Installation code'),
        environment: __('Environment'),
        database: __('Prepare the database'),
        user: __('Create the first user'),
    };

    return (
        <GuestLayout>
            <Head title={__('Installation')} />

            <Stack spacing={2}>
                <header>
                    <Typography variant="body2" color="text.secondary">
                        {__('Step :current of :total', {
                            current: STEPS.indexOf(step) + 1,
                            total: STEPS.length,
                        })}
                    </Typography>

                    <Typography component="h1" variant="h6">
                        {titles[step]}
                    </Typography>
                </header>

                {step === 'unlock' && <Unlock codePath={codePath} />}
                {step === 'environment' && (
                    <Environment groups={groups} writable={writable} />
                )}
                {step === 'database' && <Database />}
                {step === 'user' && <FirstUser />}
            </Stack>
        </GuestLayout>
    );
}
