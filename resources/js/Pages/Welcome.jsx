import ApplicationLogo from '@/Components/ApplicationLogo';
import { useTranslations } from '@/lib/i18n';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Welcome({ canLogin, canRegister, status }) {
    const __ = useTranslations();
    const { appName, auth } = usePage().props;
    const checkedAt = new Date(status.checkedAt).toLocaleString(
        document.documentElement.lang || undefined,
    );

    return (
        <>
            <Head title={appName} />

            <main className="flex min-h-screen flex-col items-center justify-center bg-gray-100 p-6 text-center">
                <ApplicationLogo className="h-20 w-20" />

                <h1 className="mt-6 text-3xl font-semibold text-gray-900">
                    {appName}
                </h1>

                <section
                    aria-labelledby="application-status"
                    className="mt-6 rounded-lg bg-white px-6 py-4 shadow"
                >
                    <h2
                        id="application-status"
                        className="font-medium text-gray-900"
                    >
                        {__('Application status')}
                    </h2>
                    <p className="mt-2 text-gray-700">
                        {__(
                            status.database
                                ? 'Database connected'
                                : 'Database unavailable',
                        )}
                    </p>
                    <p className="mt-1 text-sm text-gray-700">
                        {__('Checked at')}{' '}
                        <time dateTime={status.checkedAt}>{checkedAt}</time>
                    </p>
                </section>

                <nav aria-label={appName} className="mt-6 flex gap-4">
                    {auth.user ? (
                        <Link
                            href={route('dashboard')}
                            className="text-gray-900 underline"
                        >
                            {__('Dashboard')}
                        </Link>
                    ) : (
                        <>
                            {canLogin && (
                                <Link
                                    href={route('login')}
                                    className="text-gray-900 underline"
                                >
                                    {__('Log in')}
                                </Link>
                            )}
                            {canRegister && (
                                <Link
                                    href={route('register')}
                                    className="text-gray-900 underline"
                                >
                                    {__('Register')}
                                </Link>
                            )}
                        </>
                    )}
                </nav>
            </main>
        </>
    );
}
