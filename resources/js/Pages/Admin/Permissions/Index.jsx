import DataTable from '@/Components/DataTable/DataTable';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useTranslations } from '@/lib/i18n';
import DeleteOutlinedIcon from '@mui/icons-material/DeleteOutlined';
import EditIcon from '@mui/icons-material/Edit';
import RestoreFromTrashIcon from '@mui/icons-material/RestoreFromTrash';
import Button from '@mui/material/Button';
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
                description: __('Opens the form to change this permission.'),
                icon: EditIcon,
                href: (row) => route('admin.permissions.edit', row.id),
                visible: (row) => row.can.update,
            },
            {
                key: 'delete',
                label: __('Delete'),
                description: __('Moves this permission to the deleted records.'),
                icon: DeleteOutlinedIcon,
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
                description: __('Brings this permission back from the deleted records.'),
                icon: RestoreFromTrashIcon,
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
        <AuthenticatedLayout title={__('Permissions')}>
            <Head title={__('Permissions')} />

            <>
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
                            <Button
                                component={Link}
                                href={route('admin.permissions.create')}
                                variant="contained"
                                size="small"
                            >
                                {__('New permission')}
                            </Button>
                        )
                    }
                />
            </>
        </AuthenticatedLayout>
    );
}
