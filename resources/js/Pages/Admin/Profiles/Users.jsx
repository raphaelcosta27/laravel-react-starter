import DataTable from '@/Components/DataTable/DataTable';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useTranslations } from '@/lib/i18n';
import { Head, Link } from '@inertiajs/react';
import { useMemo } from 'react';

export default function Users({ profile, rows, query }) {
    const __ = useTranslations();
    const title = __('Users of the profile :name', { name: profile.name });

    const columns = useMemo(
        () => [
            {
                accessorKey: 'name',
                header: __('Name'),
                meta: { sortable: true, filter: 'text', required: true },
            },
            {
                accessorKey: 'email',
                header: __('Email'),
                meta: { sortable: true, filter: 'text' },
            },
            {
                accessorKey: 'is_active',
                header: __('Status'),
                cell: ({ row }) =>
                    row.original.is_active ? __('Active') : __('Inactive'),
                meta: { filter: 'boolean' },
            },
        ],
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [],
    );

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold leading-tight text-gray-800">
                    {title}
                </h1>
            }
        >
            <Head title={title} />

            <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <DataTable
                    tableKey="profile-users"
                    caption={title}
                    columns={columns}
                    rows={rows}
                    query={query}
                    emptyMessage={__('No users found.')}
                    toolbar={
                        <Link
                            href={route('admin.profiles.index')}
                            className="rounded text-sm text-gray-700 underline focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        >
                            {__('Back to list')}
                        </Link>
                    }
                />
            </div>
        </AuthenticatedLayout>
    );
}
