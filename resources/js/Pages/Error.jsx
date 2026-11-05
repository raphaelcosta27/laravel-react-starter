import { useTranslations } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/react';

const messages = {
    403: ['Access denied', 'You do not have permission to open this page.'],
    404: ['Page not found', 'The page you are looking for does not exist.'],
    419: ['Session expired', 'Reload the page and try again.'],
    429: ['Too many attempts', 'Wait a moment and try again.'],
    500: [
        'Something went wrong',
        'We could not complete your request. Try again in a few moments.',
    ],
    503: [
        'Service unavailable',
        'The application is temporarily unavailable. Try again soon.',
    ],
};

export default function Error({ status, message, canLogout = false }) {
    const __ = useTranslations();
    const [title, description] = messages[status] ?? messages[500];

    return (
        <>
            <Head title={__(title)} />

            <main className="flex min-h-screen flex-col items-center justify-center bg-gray-100 p-6 text-center">
                <p className="text-lg text-gray-700">{status}</p>
                <h1 className="mt-2 text-2xl font-semibold text-gray-900">
                    {__(title)}
                </h1>
                <p className="mt-2 text-gray-700">
                    {message ?? __(description)}
                </p>

                <div className="mt-6 flex gap-4">
                    <Link href="/" className="text-gray-900 underline">
                        {__('Back to home page')}
                    </Link>

                    {canLogout && (
                        <Link
                            as="button"
                            method="post"
                            href={route('logout')}
                            className="text-gray-900 underline"
                        >
                            {__('Log Out')}
                        </Link>
                    )}
                </div>
            </main>
        </>
    );
}
