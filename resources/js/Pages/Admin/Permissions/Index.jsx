import DataTable from '@/Components/DataTable/DataTable';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useTranslations } from '@/lib/i18n';
import { Head, Link, router } from '@inertiajs/react';
import { useMemo } from 'react';

const canEdit = (row) => row.can.update;
const updateRoute = (row) => route('admin.permissions.update', row.id);

export default function Index({ rows, query, tabs, summaries, abilities }) {
    const __ = useTranslations();

    const columns = useMemo(
        () => [
            {
                accessorKey: 'title',
                header: __('Title'),
                meta: {
                    sortable: true,
                    filter: 'text',
                    required: true,
                    editable: canEdit,
                    updateRoute,
                },
            },
            {
                accessorKey: 'code',
                header: __('Code'),
                meta: { sortable: true, filter: 'text' },
            },
            {
                accessorKey: 'description',
                header: __('Description'),
                meta: { filter: 'text', editable: canEdit, updateRoute },
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
                key: 'edit',
                label: __('Edit'),
                href: (row) => route('admin.permissions.edit', row.id),
                visible: (row) => row.can.update,
            },
            {
                key: 'delete',
                label: __('Delete'),
                variant: 'danger',
                visible: (row) => row.can.delete,
                confirm: (row) => ({
                    title: __('Delete permission'),
                    message: __('Delete the permission :name?', { name: row.title }),
                }),
                onClick: (row) =>
                    router.delete(route('admin.permissions.destroy', row.id), {
                        preserveScroll: true,
                    }),
            },
            {
                key: 'restore',
                label: __('Restore'),
                visible: (row) => row.can.restore,
                confirm: (row) => ({
                    title: __('Restore permission'),
                    message: __('Restore the permission :name?', { name: row.title }),
                }),
                onClick: (row) =>
                    router.post(
                        route('admin.permissions.restore', row.id),
                        {},
                        { preserveScroll: true },
                    ),
            },
        ],
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [],
    );

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold leading-tight text-gray-800">
                    {__('Permissions')}
                </h1>
            }
        >
            <Head title={__('Permissions')} />

            <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <DataTable
                    tableKey="permissions"
                    caption={__('Permissions')}
                    columns={columns}
                    rows={rows}
                    query={query}
                    tabs={tabs}
                    summaries={summaries}
                    rowActions={rowActions}
                    exportRoute="admin.permissions.export"
                    canExport={abilities?.export}
                    emptyMessage={__('No permissions found.')}
                    toolbar={
                        abilities?.create && (
                            <Link
                                href={route('admin.permissions.create')}
                                className="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                {__('New permission')}
                            </Link>
                        )
                    }
                />
            </div>
        </AuthenticatedLayout>
    );
}
