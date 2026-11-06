import ActionIconButton from '@/Components/ActionIconButton';
import ConfirmDialog from '@/Components/ConfirmDialog';
import DataTable from '@/Components/DataTable/DataTable';
import PersonAvatar from '@/Components/PersonAvatar';
import UserPermissionsDialog from '@/Components/UserPermissionsDialog';
import SelectField from '@/Components/SelectField';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useTranslations } from '@/lib/i18n';
import { FORM_VISIT, withListingQuery } from '@/lib/listing';
import UserFormDialog from '@/Pages/Admin/Users/Partials/UserFormDialog';
import BlockIcon from '@mui/icons-material/Block';
import CheckCircleOutlinedIcon from '@mui/icons-material/CheckCircleOutlined';
import DeleteOutlinedIcon from '@mui/icons-material/DeleteOutlined';
import EditIcon from '@mui/icons-material/Edit';
import KeyIcon from '@mui/icons-material/Key';
import MailOutlinedIcon from '@mui/icons-material/MailOutlined';
import PasswordIcon from '@mui/icons-material/Password';
import PersonAddIcon from '@mui/icons-material/PersonAdd';
import RestoreFromTrashIcon from '@mui/icons-material/RestoreFromTrash';
import Box from '@mui/material/Box';
import { Head, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

const BULK_ACTIONS = {
    activate: {
        label: 'Activate selected',
        description: 'Allows the selected users to access the system.',
        message: 'Activate the selected users?',
        icon: CheckCircleOutlinedIcon,
    },
    deactivate: {
        label: 'Deactivate selected',
        description: 'Blocks the access of the selected users.',
        message: 'Deactivate the selected users?',
        icon: BlockIcon,
    },
    delete: {
        label: 'Delete selected',
        description: 'Moves the selected users to the deleted records.',
        message: 'Delete the selected users?',
        icon: DeleteOutlinedIcon,
        variant: 'danger',
    },
};

export default function Index({
    rows,
    query,
    tabs,
    summaries,
    abilities,
    profiles = [],
    form = null,
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
                cell: ({ row }) => (
                    <Box sx={{ alignItems: 'center', display: 'flex', gap: 1 }}>
                        <PersonAvatar name={row.original.name} src={row.original.avatar_url} />
                        {row.original.name}
                    </Box>
                ),
                meta: {
                    sortable: true,
                    filter: 'text',
                    required: true,
                    essential: true,
                },
            },
            {
                accessorKey: 'email',
                header: __('Email'),
                meta: { sortable: true, filter: 'text', essential: true },
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
                    essential: true,
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
            {
                accessorKey: 'password_change_pending',
                header: __('Password change pending'),
                cell: ({ getValue }) => (getValue() ? __('Yes') : __('No')),
                meta: { filter: 'boolean', defaultHidden: true },
            },
            {
                accessorKey: 'temporary_password_expired',
                header: __('Temporary password expired'),
                cell: ({ getValue }) => (getValue() ? __('Yes') : __('No')),
                meta: { filter: 'boolean', defaultHidden: true },
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
                description: __('Opens the form to change this user.'),
                icon: EditIcon,
                href: (row) => withListingQuery(route('admin.users.edit', row.id)),
                visit: FORM_VISIT,
                visible: (row) => row.can.update,
            },
            {
                key: 'permissions',
                label: __('Permissions'),
                description: __('Manages the individual permissions of this user.'),
                icon: KeyIcon,
                href: (row) => withListingQuery(route('admin.users.permissions', row.id)),
                visit: FORM_VISIT,
                visible: (row) => row.can.permissions,
            },
            {
                key: 'password-change',
                label: __('Request password change'),
                description: __('Sends a temporary password and requires a new one.'),
                icon: PasswordIcon,
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
                description: __('Sends a new temporary password and confirmation link.'),
                icon: MailOutlinedIcon,
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
                description: __('Moves this user to the deleted records.'),
                icon: DeleteOutlinedIcon,
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
                description: __('Brings this user back from the deleted records.'),
                icon: RestoreFromTrashIcon,
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
        description: __(BULK_ACTIONS[action].description),
        icon: BULK_ACTIONS[action].icon,
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
        <AuthenticatedLayout title={__('Users')}>
            <Head title={__('Users')} />

            <>
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
                            <ActionIconButton
                                emphasis
                                icon={PersonAddIcon}
                                label={__('New user')}
                                description={__('Opens the form to register a user.')}
                                href={withListingQuery(route('admin.users.create'))}
                                {...FORM_VISIT}
                            />
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
                        <SelectField
                            id="restore-profile"
                            label={__('New profile of the user')}
                            sx={{ mt: 2 }}
                            value={restoreProfile}
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
                        </SelectField>
                    )}
                </ConfirmDialog>

                {form?.mode === 'user-permissions' && (
                    <UserPermissionsDialog key={form.user.id} form={form} />
                )}

                {form && form.mode !== 'user-permissions' && (
                    <UserFormDialog key={form.record?.id ?? 'new'} form={form} />
                )}
            </>
        </AuthenticatedLayout>
    );
}
