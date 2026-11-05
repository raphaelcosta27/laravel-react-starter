import SelectField from '@/Components/SelectField';
import TextLink from '@/Components/TextLink';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useTranslations } from '@/lib/i18n';
import { Head, router } from '@inertiajs/react';
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Paper from '@mui/material/Paper';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
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
        <AuthenticatedLayout title={title}>
            <Head title={title} />

            <Paper
                component="form"
                sx={{ maxWidth: 896, mx: 'auto', p: 3 }}
                onSubmit={submit}
            >
                {!canAdjust && (
                    <Typography variant="body2" sx={{ mb: 2 }}>
                        {__('This profile has full access to the system.')}
                    </Typography>
                )}

                {canAdjust && permissions.some(({ available }) => !available) && (
                    <Typography variant="body2" sx={{ mb: 2 }}>
                        {__('Permissions you do not have are unavailable.')}
                    </Typography>
                )}

                <Box component="ul" sx={{ listStyle: 'none', m: 0, p: 0 }}>
                    {permissions.map((permission) => (
                        <Stack
                            component="li"
                            key={permission.id}
                            direction="row"
                            useFlexGap
                            spacing={2}
                            sx={{
                                alignItems: 'center',
                                borderBottom: 1,
                                borderColor: 'divider',
                                flexWrap: 'wrap',
                                justifyContent: 'space-between',
                                py: 1.5,
                            }}
                        >
                            <div>
                                <Typography variant="subtitle2" component="p">
                                    {permission.title}
                                </Typography>
                                <Typography variant="body2" color="text.secondary">
                                    {permission.active
                                        ? __('Active')
                                        : __('Inactive')}
                                    {' · '}
                                    <span>{__(ORIGINS[permission.origin])}</span>
                                </Typography>
                            </div>

                            {canAdjust && (
                                <SelectField
                                    id={`adjustment-${permission.id}`}
                                    label={__('Adjustment of :name', {
                                        name: permission.title,
                                    })}
                                    disabled={!permission.available}
                                    fullWidth={false}
                                    sx={{ minWidth: 260 }}
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
                                </SelectField>
                            )}
                        </Stack>
                    ))}
                </Box>

                <Stack
                    direction="row"
                    spacing={2}
                    sx={{ alignItems: 'center', mt: 3 }}
                >
                    {canAdjust && (
                        <Button type="submit" variant="contained">
                            {__('Save')}
                        </Button>
                    )}

                    <TextLink href={route('admin.users.index')}>
                        {__('Back to list')}
                    </TextLink>
                </Stack>
            </Paper>
        </AuthenticatedLayout>
    );
}
