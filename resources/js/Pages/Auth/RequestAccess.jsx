import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { useTranslations } from '@/lib/i18n';
import { Head, Link, useForm } from '@inertiajs/react';

export default function RequestAccess({ status }) {
    const __ = useTranslations();
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    const submit = (event) => {
        event.preventDefault();

        post(route('access.send'));
    };

    return (
        <GuestLayout>
            <Head title={__('Request new access')} />

            <h1 className="mb-4 text-lg font-medium text-gray-900">
                {__('Request new access')}
            </h1>

            <p className="mb-4 text-sm text-gray-700">
                {__(
                    'If your temporary password has expired, enter your email to receive a new password and a new link.',
                )}
            </p>

            {status && (
                <div
                    role="status"
                    className="mb-4 text-sm font-medium text-green-700"
                >
                    {status}
                </div>
            )}

            <form onSubmit={submit}>
                <InputLabel htmlFor="email" value={__('Email')} />

                <TextInput
                    id="email"
                    type="email"
                    name="email"
                    value={data.email}
                    className="mt-1 block w-full"
                    autoComplete="username"
                    isFocused={true}
                    onChange={(event) => setData('email', event.target.value)}
                />

                <InputError message={errors.email} className="mt-2" />

                <div className="mt-4 flex items-center justify-between">
                    <Link
                        href={route('login')}
                        className="text-sm text-gray-700 underline"
                    >
                        {__('Log in')}
                    </Link>

                    <PrimaryButton disabled={processing}>
                        {__('Send')}
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
