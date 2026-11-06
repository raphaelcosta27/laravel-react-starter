import ActionIconButton from '@/Components/ActionIconButton';
import DataTable from '@/Components/DataTable/DataTable';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useTranslations } from '@/lib/i18n';
import { Head } from '@inertiajs/react';
import ArrowBackIcon from '@mui/icons-material/ArrowBack';
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
        <AuthenticatedLayout title={title}>
            <Head title={title} />

            <DataTable
                tableKey="profile-users"
                caption={title}
                columns={columns}
                rows={rows}
                query={query}
                emptyMessage={__('No users found.')}
                toolbar={
                    <ActionIconButton
                        icon={ArrowBackIcon}
                        label={__('Back to list')}
                        description={__('Returns to the list of profiles.')}
                        href={route('admin.profiles.index')}
                    />
                }
            />
        </AuthenticatedLayout>
    );
}
