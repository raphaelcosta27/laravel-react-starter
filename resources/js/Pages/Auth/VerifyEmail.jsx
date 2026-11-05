import PrimaryButton from '@/Components/PrimaryButton';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useTranslations } from '@/lib/i18n';

export default function VerifyEmail({ status }) {
    const __ = useTranslations();
    const { post, processing } = useForm({});

    const submit = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <GuestLayout>
            <Head title={__('Email Verification')} />

            <h1 className="mb-4 text-lg font-medium text-gray-900">
                {__('Email Verification')}
            </h1>

            <div className="mb-4 text-sm text-gray-600">
                {__("Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.")}
            </div>

            {status === 'verification-link-expired' && (
                <div role="status" className="mb-4 text-sm font-medium text-red-700">
                    {__('This link has expired. Request a new one below.')}
                </div>
            )}

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-sm font-medium text-green-700">
                    {__('A new verification link has been sent to the email address you provided during registration.')}
                </div>
            )}

            <form onSubmit={submit}>
                <div className="mt-4 flex items-center justify-between">
                    <PrimaryButton disabled={processing}>
                        {__('Resend Verification Email')}
                    </PrimaryButton>

                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        {__('Log Out')}
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
