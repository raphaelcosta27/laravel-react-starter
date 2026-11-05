import DataTable from '@/Components/DataTable/DataTable';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useTranslations } from '@/lib/i18n';
import { Head, Link, router } from '@inertiajs/react';
import { useMemo } from 'react';

const canEdit = (row) => row.can.update;
const updateRoute = (row) => route('admin.profiles.update', row.id);

export default function Index({ rows, query, tabs, summaries, abilities }) {
    const __ = useTranslations();

    const columns = useMemo(
        () => [
            {
                accessorKey: 'name',
                header: __('Name'),
                meta: {
                    sortable: true,
                    filter: 'text',
                    required: true,
                    editable: canEdit,
                    updateRoute,
                },
            },
            {
                accessorKey: 'description',
                header: __('Description'),
                meta: { filter: 'text', editable: canEdit, updateRoute },
            },
            {
                accessorKey: 'users_count',
                header: __('Users'),
                meta: {
                    sortable: true,
                    filter: 'number',
                    align: 'end',
                    summary: 'sum',
                },
            },
            ...(query.tab === 'trashed'
                ? [{ accessorKey: 'deleted_at', header: __('Deleted at') }]
                : []),
        ],
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [query.tab],
    );

    const rowActions = useMemo(
        () => () => [
            {
                key: 'users',
                label: __('View Users'),
                href: (row) => route('admin.profiles.users', row.id),
                visible: (row) => row.can.users,
            },
            {
                key: 'edit',
                label: __('Edit'),
                href: (row) => route('admin.profiles.edit', row.id),
                visible: (row) => row.can.update,
            },
            {
                key: 'delete',
                label: __('Delete'),
                variant: 'danger',
                visible: (row) => row.can.delete,
                confirm: (row) => ({
                    title: __('Delete profile'),
                    message: __('Delete the profile :name?', { name: row.name }),
                }),
                onClick: (row) =>
                    router.delete(route('admin.profiles.destroy', row.id), {
                        preserveScroll: true,
                    }),
            },
            {
                key: 'restore',
                label: __('Restore'),
                visible: (row) => row.can.restore,
                confirm: (row) => ({
                    title: __('Restore profile'),
                    message: __('Restore the profile :name?', { name: row.name }),
                }),
                onClick: (row) =>
                    router.post(
                        route('admin.profiles.restore', row.id),
                        {},
                        { preserveScroll: true },
                    ),
            },
        ],
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [],
    );

    const renderDetail = (profile) =>
        profile.permissions.length > 0 ? (
            <ul className="flex flex-wrap gap-2">
                {profile.permissions.map((permission) => (
                    <li
                        key={permission.id}
                        className="rounded bg-white px-2 py-1 text-sm text-gray-800 shadow-sm"
                    >
                        {permission.title}
                    </li>
                ))}
            </ul>
        ) : (
            <p>{__('No permissions in this profile.')}</p>
        );

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold leading-tight text-gray-800">
                    {__('Profiles')}
                </h1>
            }
        >
            <Head title={__('Profiles')} />

            <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <DataTable
                    tableKey="profiles"
                    caption={__('Profiles')}
                    columns={columns}
                    rows={rows}
                    query={query}
                    tabs={tabs}
                    summaries={summaries}
                    rowActions={rowActions}
                    renderDetail={renderDetail}
                    exportRoute="admin.profiles.export"
                    canExport={abilities?.export}
                    emptyMessage={__('No profiles found.')}
                    toolbar={
                        abilities?.create && (
                            <Link
                                href={route('admin.profiles.create')}
                                className="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                {__('New profile')}
                            </Link>
                        )
                    }
                />
            </div>
        </AuthenticatedLayout>
    );
}
