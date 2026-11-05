import ConfirmDialog from '@/Components/ConfirmDialog';
import DataTable from '@/Components/DataTable/DataTable';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useTranslations } from '@/lib/i18n';
import { Head, Link, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

const BULK_ACTIONS = {
    activate: { label: 'Activate selected', message: 'Activate the selected users?' },
    deactivate: { label: 'Deactivate selected', message: 'Deactivate the selected users?' },
    delete: { label: 'Delete selected', message: 'Delete the selected users?', variant: 'danger' },
};

export default function Index({
    rows,
    query,
    tabs,
    summaries,
    abilities,
    profiles = [],
}) {
    const __ = useTranslations();
    const [statusChange, setStatusChange] = useState(null);
    const [restoring, setRestoring] = useState(null);
    const [restoreProfile, setRestoreProfile] = useState('');

    const columns = useMemo(
        () => [
            {
                accessorKey: 'name',
                header: __('Name'),
                meta: {
                    sortable: true,
                    filter: 'text',
                    required: true,
                    editable: (row) => row.can.update,
                    updateRoute: (row) => route('admin.users.update', row.id),
                },
            },
            {
                accessorKey: 'email',
                header: __('Email'),
                meta: { sortable: true, filter: 'text' },
            },
            {
                id: 'profile_id',
                accessorKey: 'profile_name',
                header: __('Profile'),
                meta: {
                    sortable: true,
                    filter: { type: 'multiselect', options: profiles },
                },
            },
            {
                accessorKey: 'is_active',
                header: __('Status'),
                meta: {
                    filter: 'boolean',
                    toggle: {
                        label: (row) =>
                            __(':column of :name', {
                                column: __('Status'),
                                name: row.name,
                            }),
                        disabled: (row) => !row.can.status,
                        onToggle: (row, value) =>
                            setStatusChange({ row, value }),
                    },
                },
            },
            {
                accessorKey: 'confirmed',
                header: __('Account confirmation'),
                cell: ({ row }) =>
                    row.original.confirmed ? __('Confirmed') : __('Pending'),
                meta: { filter: 'boolean' },
            },
            ...(query.tab === 'trashed'
                ? [{ accessorKey: 'deleted_at', header: __('Deleted at') }]
                : []),
        ],
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [profiles, query.tab],
    );

    const rowActions = useMemo(
        () => () => [
            {
                key: 'edit',
                label: __('Edit'),
                href: (row) => route('admin.users.edit', row.id),
                visible: (row) => row.can.update,
            },
            {
                key: 'permissions',
                label: __('Permissions'),
                href: (row) => route('admin.users.permissions', row.id),
                visible: (row) => row.can.permissions,
            },
            {
                key: 'password-change',
                label: __('Request password change'),
                menu: true,
                visible: (row) => row.can.passwordChange,
                confirm: (row) => ({
                    title: __('Request password change'),
                    message: __('The current password of :name will stop working, and a temporary one will be sent by email.', { name: row.name }),
                }),
                onClick: (row) =>
                    router.post(
                        route('admin.users.password-change', row.id),
                        {},
                        { preserveScroll: true },
                    ),
            },
            {
                key: 'authentication-email',
                label: __('Send authentication email'),
                menu: true,
                visible: (row) => row.can.authenticationEmail,
                confirm: (row) => ({
                    title: __('Send authentication email'),
                    message: __('A new temporary password and link will be sent to :name.', { name: row.name }),
                }),
                onClick: (row) =>
                    router.post(
                        route('admin.users.authentication-email', row.id),
                        {},
                        { preserveScroll: true },
                    ),
            },
            {
                key: 'delete',
                label: __('Delete'),
                variant: 'danger',
                visible: (row) => row.can.delete,
                confirm: (row) => ({
                    title: __('Delete user'),
                    message: __('Delete the user :name?', { name: row.name }),
                }),
                onClick: (row) =>
                    router.delete(route('admin.users.destroy', row.id), {
                        preserveScroll: true,
                    }),
            },
            {
                key: 'restore',
                label: __('Restore'),
                visible: (row) => row.can.restore,
                onClick: (row) => {
                    setRestoreProfile('');
                    setRestoring(row);
                },
            },
        ],
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [],
    );

    const bulkActions = (abilities?.bulk ?? []).map((action) => ({
        key: action,
        label: __(BULK_ACTIONS[action].label),
        variant: BULK_ACTIONS[action].variant,
        confirm: {
            title: __(BULK_ACTIONS[action].label),
            message: __(BULK_ACTIONS[action].message),
        },
        onClick: (selected) =>
            router.post(
                route('admin.users.bulk'),
                { action, ids: selected.map((row) => row.id) },
                { preserveScroll: true },
            ),
    }));

    const confirmStatus = () => {
        router.patch(
            route('admin.users.status', statusChange.row.id),
            { is_active: statusChange.value },
            { preserveScroll: true },
        );

        setStatusChange(null);
    };

    const confirmRestore = () => {
        if (restoring.profile_deleted && restoreProfile === '') {
            return;
        }

        router.post(
            route('admin.users.restore', restoring.id),
            restoring.profile_deleted ? { profile_id: restoreProfile } : {},
            { preserveScroll: true },
        );

        setRestoring(null);
    };

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold leading-tight text-gray-800">
                    {__('Users')}
                </h1>
            }
        >
            <Head title={__('Users')} />

            <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <DataTable
                    tableKey="users"
                    caption={__('Users')}
                    columns={columns}
                    rows={rows}
                    query={query}
                    tabs={tabs}
                    summaries={summaries}
                    rowActions={rowActions}
                    bulkActions={bulkActions}
                    exportRoute="admin.users.export"
                    canExport={abilities?.export}
                    emptyMessage={__('No users found.')}
                    toolbar={
                        abilities?.create && (
                            <Link
                                href={route('admin.users.create')}
                                className="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                {__('New user')}
                            </Link>
                        )
                    }
                />

                <ConfirmDialog
                    show={statusChange !== null}
                    title={
                        statusChange?.value
                            ? __('Activate user')
                            : __('Deactivate user')
                    }
                    message={
                        statusChange?.value
                            ? __('Activate the user :name?', { name: statusChange?.row.name })
                            : __('Deactivate the user :name?', { name: statusChange?.row.name })
                    }
                    onConfirm={confirmStatus}
                    onClose={() => setStatusChange(null)}
                />

                <ConfirmDialog
                    show={restoring !== null}
                    title={__('Restore user')}
                    message={__('Restore the user :name?', { name: restoring?.name })}
                    onConfirm={confirmRestore}
                    onClose={() => setRestoring(null)}
                >
                    {restoring?.profile_deleted && (
                        <div className="mt-4">
                            <label
                                htmlFor="restore-profile"
                                className="block text-sm font-medium text-gray-700"
                            >
                                {__('New profile of the user')}
                            </label>
                            <select
                                id="restore-profile"
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={restoreProfile}
                                required
                                onChange={(event) =>
                                    setRestoreProfile(event.target.value)
                                }
                            >
                                <option value="">{__('Select')}</option>
                                {profiles.map((option) => (
                                    <option key={option.value} value={option.value}>
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </div>
                    )}
                </ConfirmDialog>
            </div>
        </AuthenticatedLayout>
    );
}
