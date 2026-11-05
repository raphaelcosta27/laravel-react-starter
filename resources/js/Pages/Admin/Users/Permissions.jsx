import PrimaryButton from '@/Components/PrimaryButton';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useTranslations } from '@/lib/i18n';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

const ORIGINS = {
    profile: 'Comes from the profile',
    grant: 'Individual grant',
    revoke: 'Individual revocation',
    none: 'No access',
};

export default function Permissions({ user, permissions, canAdjust }) {
    const __ = useTranslations();
    const title = __('Permissions of :name', { name: user.name });

    const [adjustments, setAdjustments] = useState(() =>
        Object.fromEntries(
            permissions.map((permission) => [
                permission.id,
                permission.adjustment ?? '',
            ]),
        ),
    );

    const submit = (event) => {
        event.preventDefault();

        router.put(
            route('admin.users.permissions.update', user.id),
            {
                adjustments: Object.entries(adjustments)
                    .filter(([, effect]) => effect !== '')
                    .map(([id, effect]) => ({
                        permission_id: Number(id),
                        effect,
                    })),
            },
            { preserveScroll: true },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold leading-tight text-gray-800">
                    {title}
                </h1>
            }
        >
            <Head title={title} />

            <div className="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
                <form
                    onSubmit={submit}
                    className="rounded-md bg-white p-6 shadow-sm"
                >
                    {!canAdjust && (
                        <p className="mb-4 text-sm text-gray-700">
                            {__('This profile has full access to the system.')}
                        </p>
                    )}

                    <ul className="divide-y divide-gray-200">
                        {permissions.map((permission) => (
                            <li
                                key={permission.id}
                                className="flex flex-wrap items-center justify-between gap-3 py-3"
                            >
                                <div>
                                    <p className="text-sm font-medium text-gray-900">
                                        {permission.title}
                                    </p>
                                    <p className="text-sm text-gray-600">
                                        {permission.active
                                            ? __('Active')
                                            : __('Inactive')}
                                        {' · '}
                                        <span>{__(ORIGINS[permission.origin])}</span>
                                    </p>
                                </div>

                                {canAdjust && (
                                    <div>
                                        <label
                                            htmlFor={`adjustment-${permission.id}`}
                                            className="sr-only"
                                        >
                                            {__('Adjustment of :name', {
                                                name: permission.title,
                                            })}
                                        </label>
                                        <select
                                            id={`adjustment-${permission.id}`}
                                            className="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            value={adjustments[permission.id]}
                                            onChange={(event) =>
                                                setAdjustments((current) => ({
                                                    ...current,
                                                    [permission.id]: event.target.value,
                                                }))
                                            }
                                        >
                                            <option value="">{__('Follow the profile')}</option>
                                            <option value="grant">{__('Grant')}</option>
                                            <option value="revoke">{__('Revoke')}</option>
                                        </select>
                                    </div>
                                )}
                            </li>
                        ))}
                    </ul>

                    <div className="mt-6 flex items-center gap-4">
                        {canAdjust && <PrimaryButton>{__('Save')}</PrimaryButton>}

                        <Link
                            href={route('admin.users.index')}
                            className="rounded text-sm text-gray-700 underline focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            {__('Back to list')}
                        </Link>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
