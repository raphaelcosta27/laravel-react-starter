import DataTable from '@/Components/DataTable/DataTable';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useTranslations } from '@/lib/i18n';
import { FORM_VISIT, withListingQuery } from '@/lib/listing';
import ProfileFormDialog from '@/Pages/Admin/Profiles/Partials/ProfileFormDialog';
import DeleteOutlinedIcon from '@mui/icons-material/DeleteOutlined';
import EditIcon from '@mui/icons-material/Edit';
import GroupIcon from '@mui/icons-material/Group';
import RestoreFromTrashIcon from '@mui/icons-material/RestoreFromTrash';
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Chip from '@mui/material/Chip';
import { Head, Link, router } from '@inertiajs/react';
import { useMemo } from 'react';


export default function Index({ rows, query, tabs, summaries, abilities, form = null }) {
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
                },
            },
            {
                accessorKey: 'description',
                header: __('Description'),
                meta: { filter: 'text' },
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
                description: __('Lists the users of this profile.'),
                icon: GroupIcon,
                href: (row) => route('admin.profiles.users', row.id),
                visible: (row) => row.can.users,
            },
            {
                key: 'edit',
                label: __('Edit'),
                description: __('Opens the form to change this profile.'),
                icon: EditIcon,
                href: (row) => withListingQuery(route('admin.profiles.edit', row.id)),
                visit: FORM_VISIT,
                visible: (row) => row.can.update,
            },
            {
                key: 'delete',
                label: __('Delete'),
                description: __('Moves this profile to the deleted records.'),
                icon: DeleteOutlinedIcon,
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
                description: __('Brings this profile back from the deleted records.'),
                icon: RestoreFromTrashIcon,
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
            <Box
                component="ul"
                sx={{ display: 'flex', flexWrap: 'wrap', gap: 1, listStyle: 'none', m: 0, p: 0 }}
            >
                {profile.permissions.map((permission) => (
                    <li key={permission.id}>
                        <Chip label={permission.title} size="small" variant="outlined" />
                    </li>
                ))}
            </Box>
        ) : (
            <p>{__('No permissions in this profile.')}</p>
        );

    return (
        <AuthenticatedLayout title={__('Profiles')}>
            <Head title={__('Profiles')} />

            <>
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
                            <Button
                                component={Link}
                                href={withListingQuery(route('admin.profiles.create'))}
                                {...FORM_VISIT}
                                variant="contained"
                                size="small"
                            >
                                {__('New profile')}
                            </Button>
                        )
                    }
                />

                {form && <ProfileFormDialog key={form.record?.id ?? 'new'} form={form} />}
            </>
        </AuthenticatedLayout>
    );
}
