import PostLink from '@/Components/PostLink';
import SelectField from '@/Components/SelectField';
import { formatCpf } from '@/lib/cpf';
import { fieldProps } from '@/lib/form';
import { useTranslations } from '@/lib/i18n';
import { useForm, usePage } from '@inertiajs/react';
import Alert from '@mui/material/Alert';
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';
import TextField from '@mui/material/TextField';
import Typography from '@mui/material/Typography';

export default function UpdateProfileInformation({
    mustVerifyEmail,
    status,
    personalData = {},
    genders = [],
    cpfRequired = false,
}) {
    const __ = useTranslations();
    const user = usePage().props.auth.user;

    const form = useForm({
        name: user.name,
        preferred_name: personalData.preferred_name ?? '',
        birth_date: personalData.birth_date ?? '',
        gender: genders.some(({ value }) => value === personalData.gender)
            ? personalData.gender
            : '',
        cpf: formatCpf(personalData.cpf),
        email: user.email,
    });

    const submit = (event) => {
        event.preventDefault();

        form.patch(route('profile.update'));
    };

    return (
        <Stack component="section" spacing={3} sx={{ maxWidth: 576 }}>
            <header>
                <Typography component="h2" variant="h6">
                    {__('Profile Information')}
                </Typography>

                <Typography variant="body2" color="text.secondary">
                    {__("Update your account's profile information and email address.")}
                </Typography>
            </header>

            <Stack component="form" spacing={3} onSubmit={submit}>
                <TextField
                    {...fieldProps(form, 'name', { required: true })}
                    label={__('Full name')}
                    autoComplete="name"
                    autoFocus
                />

                <TextField
                    {...fieldProps(form, 'preferred_name', {
                        htmlInput: { maxLength: 100 },
                    })}
                    label={__('How should we call you?')}
                    autoComplete="nickname"
                />

                <TextField
                    {...fieldProps(form, 'birth_date')}
                    type="date"
                    label={__('Date of birth')}
                    autoComplete="bday"
                    slotProps={{ inputLabel: { shrink: true } }}
                />

                <SelectField
                    {...fieldProps(form, 'gender')}
                    size="medium"
                    label={__('Gender')}
                >
                    <option value="">{__('Select')}</option>
                    {genders.map(({ value, label }) => (
                        <option key={value} value={value}>
                            {label}
                        </option>
                    ))}
                </SelectField>

                <TextField
                    {...fieldProps(form, 'cpf', {
                        required: cpfRequired,
                        htmlInput: { inputMode: 'numeric', maxLength: 14 },
                    })}
                    label="CPF"
                    autoComplete="off"
                    onChange={(event) =>
                        form.setData('cpf', formatCpf(event.target.value))
                    }
                />

                <TextField
                    {...fieldProps(form, 'email', { required: true })}
                    type="email"
                    label={__('Email')}
                    autoComplete="username"
                />

                {mustVerifyEmail && user.email_verified_at === null && (
                    <Stack spacing={1} sx={{ alignItems: 'flex-start' }}>
                        <Typography variant="body2">
                            {__('Your email address is unverified.')}
                        </Typography>

                        <Button
                            component={PostLink}
                            href={route('verification.send')}
                            size="small"
                        >
                            {__('Click here to re-send the verification email.')}
                        </Button>

                        {status === 'verification-link-sent' && (
                            <Alert severity="success" role="status">
                                {__('A new verification link has been sent to your email address.')}
                            </Alert>
                        )}
                    </Stack>
                )}

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
