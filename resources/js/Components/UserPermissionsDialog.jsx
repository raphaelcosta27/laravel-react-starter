import FormDialog from '@/Components/FormDialog';
import PermissionGroups from '@/Components/PermissionGroups';
import { useTranslations } from '@/lib/i18n';
import { closeForm, FORM_SUBMIT } from '@/lib/listing';
import { router } from '@inertiajs/react';
import BlockIcon from '@mui/icons-material/Block';
import GroupsOutlinedIcon from '@mui/icons-material/GroupsOutlined';
import PersonOutlinedIcon from '@mui/icons-material/PersonOutlined';
import Alert from '@mui/material/Alert';
import Chip from '@mui/material/Chip';
import { useMemo, useState } from 'react';

const LISTS = {
    users: 'admin.users.index',
    permissions: 'admin.permissions.index',
};

const BADGES = {
    profile: { label: 'From the profile', icon: GroupsOutlinedIcon, color: 'default' },
    individual: { label: 'Individual', icon: PersonOutlinedIcon, color: 'primary' },
    revoked: { label: 'Revoked', icon: BlockIcon, color: 'error' },
};

function badgeOf(permission, checked) {
    if (permission.inProfile) {
        return checked ? 'profile' : 'revoked';
    }

    return checked ? 'individual' : null;
}

/**
 * Marcada é concedida, e desmarcada é revogada. Só a diferença em relação ao
 * perfil vira ajuste individual.
 */
export default function UserPermissionsDialog({ form }) {
    const __ = useTranslations();
    const { user, canAdjust, origin, modules } = form;

    const permissions = useMemo(
        () => modules.flatMap((module) => module.permissions),
        [modules],
    );
    const initial = useMemo(
        () => permissions.filter(({ assigned }) => assigned).map(({ id }) => id),
        [permissions],
    );

    const [checked, setChecked] = useState(initial);
    const [processing, setProcessing] = useState(false);

    const isDirty =
        checked.length !== initial.length ||
        checked.some((id) => !initial.includes(id));

    const toggle = (id, value) =>
        setChecked((current) =>
            value ? [...current, id] : current.filter((item) => item !== id),
        );

    const adjustments = () =>
        permissions.flatMap(({ id, inProfile }) => {
            const isChecked = checked.includes(id);

            if (isChecked === inProfile) {
                return [];
            }

            return [{ permission_id: id, effect: isChecked ? 'grant' : 'revoke' }];
        });

    const submit = () =>
        router.put(
            route('admin.users.permissions.update', user.id),
            { origin, adjustments: adjustments() },
            {
                ...FORM_SUBMIT,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
            },
        );

    const renderExtra = (permission, isChecked) => {
        const badge = BADGES[badgeOf(permission, isChecked)];

        if (!badge) {
            return null;
        }

        const Icon = badge.icon;

        return (
            <Chip
                size="small"
                variant="outlined"
                color={badge.color}
                icon={<Icon />}
                label={__(badge.label)}
            />
        );
    };

    const renderNotice = (module) => {
        const others = module.permissions.some(
            ({ id }) => id !== module.view && checked.includes(id),
        );

        if (module.view === null || checked.includes(module.view) || !others) {
            return null;
        }

        return (
            <Alert severity="warning">
                {__('Without View, the other permissions of this module have no effect.')}
            </Alert>
        );
    };

    return (
        <FormDialog
            open
            title={__('Permissions of :name', { name: user.name })}
            processing={processing}
            isDirty={isDirty}
            hideSubmit={!canAdjust}
            onSubmit={submit}
            onClose={() => closeForm(route(LISTS[origin]))}
        >
            {canAdjust ? (
                <PermissionGroups
                    modules={modules}
                    checked={checked}
                    renderExtra={renderExtra}
                    renderNotice={renderNotice}
                    onToggle={toggle}
                />
            ) : (
                <Alert severity="info">
                    {__('This profile has full access to the system.')}
                </Alert>
            )}
        </FormDialog>
    );
}
