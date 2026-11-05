import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useTranslations } from '@/lib/i18n';

export default function Login({ status, canResetPassword, canRegister }) {
    const __ = useTranslations();
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title={__('Log in')} />

            <h1 className="mb-4 text-lg font-medium text-gray-900">
                {__('Log in')}
            </h1>

            {status && (
                <div
                    role="status"
                    className="mb-4 text-sm font-medium text-green-700"
                >
                    {status}
                </div>
            )}

            <form onSubmit={submit}>
                <div>
                    <InputLabel htmlFor="email" value={__('Email')} />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                    />

                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password" value={__('Password')} />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                    />

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="mt-4 block">
                    <label className="flex items-center">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) =>
                                setData('remember', e.target.checked)
                            }
                        />
                        <span className="ms-2 text-sm text-gray-600">
                            {__('Remember me')}
                        </span>
                    </label>
                </div>

                <div className="mt-4 flex items-center justify-end">
                    {canResetPassword && (
                        <Link
                            href={route('password.request')}
                            className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            {__('Forgot your password?')}
                        </Link>
                    )}

                    <PrimaryButton className="ms-4" disabled={processing}>
                        {__('Log in')}
                    </PrimaryButton>
                </div>
            </form>

            <div className="mt-4 flex flex-col gap-2 text-sm">
                <Link
                    href={route('access.request')}
                    className="text-gray-700 underline"
                >
                    {__('Request new access')}
                </Link>

                {canRegister && (
                    <Link
                        href={route('register')}
                        className="text-gray-700 underline"
                    >
                        {__('Register')}
                    </Link>
                )}
            </div>
        </GuestLayout>
    );
}
