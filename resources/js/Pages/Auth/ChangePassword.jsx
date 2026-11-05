import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { useTranslations } from '@/lib/i18n';
import { Head, Link, useForm } from '@inertiajs/react';

function PasswordField({ id, label, value, error, onChange, autoComplete }) {
    return (
        <div className="mt-4">
            <InputLabel htmlFor={id} value={label} />

            <TextInput
                id={id}
                type="password"
                value={value}
                className="mt-1 block w-full"
                autoComplete={autoComplete}
                aria-invalid={error ? 'true' : undefined}
                aria-describedby={error ? `${id}-error` : undefined}
                onChange={(event) => onChange(event.target.value)}
            />

            <InputError id={`${id}-error`} message={error} className="mt-2" />
        </div>
    );
}

export default function ChangePassword() {
    const __ = useTranslations();
    const { data, setData, put, processing, errors, reset } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (event) => {
        event.preventDefault();

        put(route('password.update'), { onError: () => reset() });
    };

    return (
        <GuestLayout>
            <Head title={__('Change your password')} />

            <h1 className="text-lg font-medium text-gray-900">
                {__('Change your password')}
            </h1>

            <p className="mt-2 text-sm text-gray-700">
                {__(
                    'For your security, you need to set a new password before continuing.',
                )}
            </p>

            <form onSubmit={submit}>
                <PasswordField
                    id="current_password"
                    label={__('Current Password')}
                    value={data.current_password}
                    error={errors.current_password}
                    autoComplete="current-password"
                    onChange={(value) => setData('current_password', value)}
                />

                <PasswordField
                    id="password"
                    label={__('New Password')}
                    value={data.password}
                    error={errors.password}
                    autoComplete="new-password"
                    onChange={(value) => setData('password', value)}
                />

                <PasswordField
                    id="password_confirmation"
                    label={__('Confirm Password')}
                    value={data.password_confirmation}
                    error={errors.password_confirmation}
                    autoComplete="new-password"
                    onChange={(value) =>
                        setData('password_confirmation', value)
                    }
                />

                <div className="mt-4 flex items-center justify-between">
                    <Link
                        as="button"
                        method="post"
                        href={route('logout')}
                        className="text-sm text-gray-700 underline"
                    >
                        {__('Log Out')}
                    </Link>

                    <PrimaryButton disabled={processing}>
                        {__('Save')}
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
