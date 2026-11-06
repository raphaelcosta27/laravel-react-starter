import FieldIcon from '@/Components/FieldIcon';
import PostLink from '@/Components/PostLink';
import SelectField from '@/Components/SelectField';
import { formatCpf, isValidCpf } from '@/lib/cpf';
import { fieldProps } from '@/lib/form';
import { useTranslations } from '@/lib/i18n';
import { useForm, usePage } from '@inertiajs/react';
import BadgeOutlinedIcon from '@mui/icons-material/BadgeOutlined';
import CakeOutlinedIcon from '@mui/icons-material/CakeOutlined';
import EmailOutlinedIcon from '@mui/icons-material/EmailOutlined';
import FaceOutlinedIcon from '@mui/icons-material/FaceOutlined';
import PersonOutlinedIcon from '@mui/icons-material/PersonOutlined';
import WcOutlinedIcon from '@mui/icons-material/WcOutlined';
import ForwardToInboxOutlinedIcon from '@mui/icons-material/ForwardToInboxOutlined';
import SaveOutlinedIcon from '@mui/icons-material/SaveOutlined';
import Alert from '@mui/material/Alert';
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';
import TextField from '@mui/material/TextField';
import Typography from '@mui/material/Typography';
import { useState } from 'react';

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

    const [cpfLeft, setCpfLeft] = useState(false);

    const cpfDigits = form.data.cpf.replace(/\D/g, '').length;
    const cpfBlocked = cpfDigits > 0 && !isValidCpf(form.data.cpf);
    // Enquanto a pessoa digita, faltar número ainda não é erro.
    const cpfWarning = cpfBlocked && (cpfDigits === 11 || cpfLeft);
    const cpfError = cpfWarning ? __('Enter a valid CPF.') : form.errors.cpf;

    const cpfField = fieldProps(form, 'cpf', {
        required: cpfRequired,
        htmlInput: { inputMode: 'numeric', maxLength: 14 },
        adornment: <FieldIcon icon={BadgeOutlinedIcon} />,
    });

    const changeCpf = (event) => {
        setCpfLeft(false);
        form.clearErrors('cpf');
        form.setData('cpf', formatCpf(event.target.value));
    };

    const submit = (event) => {
        event.preventDefault();

        if (cpfBlocked) {
            setCpfLeft(true);

            return;
        }

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
                    {...fieldProps(form, 'name', {
                        required: true,
                        adornment: <FieldIcon icon={PersonOutlinedIcon} />,
                    })}
                    label={__('Full name')}
                    autoComplete="name"
                    autoFocus
                />

                <TextField
                    {...fieldProps(form, 'preferred_name', {
                        htmlInput: { maxLength: 100 },
                        adornment: <FieldIcon icon={FaceOutlinedIcon} />,
                    })}
                    label={__('How should we call you?')}
                    autoComplete="nickname"
                />

                <TextField
                    {...fieldProps(form, 'birth_date', {
                        adornment: <FieldIcon icon={CakeOutlinedIcon} />,
                    })}
                    type="date"
                    label={__('Date of birth')}
                    autoComplete="bday"
                />

                <SelectField
                    {...fieldProps(form, 'gender', {
                        adornment: <FieldIcon icon={WcOutlinedIcon} />,
                    })}
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
                    {...cpfField}
                    error={Boolean(cpfError)}
                    helperText={cpfError}
                    slotProps={{
                        ...cpfField.slotProps,
                        formHelperText: { role: 'alert' },
                    }}
                    label="CPF"
                    autoComplete="off"
                    onChange={changeCpf}
                    onBlur={() => setCpfLeft(true)}
                />

                <TextField
                    {...fieldProps(form, 'email', {
                        required: true,
                        adornment: <FieldIcon icon={EmailOutlinedIcon} />,
                    })}
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
                            startIcon={<ForwardToInboxOutlinedIcon />}
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
                        startIcon={<SaveOutlinedIcon />}
                        type="submit"
                        variant="contained"
                        disabled={form.processing || cpfBlocked}
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
