import ActionIconButton from '@/Components/ActionIconButton';
import ConfirmDialog from '@/Components/ConfirmDialog';
import { useTranslations } from '@/lib/i18n';
import { Link } from '@inertiajs/react';
import MoreVertIcon from '@mui/icons-material/MoreVert';
import Box from '@mui/material/Box';
import ListItemIcon from '@mui/material/ListItemIcon';
import ListItemText from '@mui/material/ListItemText';
import Menu from '@mui/material/Menu';
import MenuItem from '@mui/material/MenuItem';
import { useId, useState } from 'react';

const COLORS = { default: 'default', danger: 'error' };

const resolve = (value, row) =>
    typeof value === 'function' ? value(row) : value;

export default function RowActions({ row, actions = [], label }) {
    const __ = useTranslations();
    const menuId = useId();
    const [pending, setPending] = useState(null);
    const [anchor, setAnchor] = useState(null);

    const visible = actions.filter((action) => action.visible?.(row) ?? true);

    if (visible.length === 0) {
        return null;
    }

    const run = (action) => {
        setAnchor(null);

        if (action.confirm) {
            setPending(action);

            return;
        }

        action.onClick?.(row);
    };

    const confirm = () => {
        pending.onClick?.(row);
        setPending(null);
    };

    const color = (action) => COLORS[action.variant ?? 'default'];
    const inline = visible.filter((action) => !action.menu);
    const grouped = visible.filter((action) => action.menu);
    const details = pending ? resolve(pending.confirm, row) : null;

    return (
        <Box
            role="group"
            aria-label={label}
            sx={{ alignItems: 'center', display: 'flex', gap: 0.5 }}
        >
            {inline.map((action) => (
                <ActionIconButton
                    key={action.key}
                    icon={action.icon}
                    label={action.label}
                    description={action.description}
                    color={color(action)}
                    disabled={action.disabled?.(row) ?? false}
                    {...(action.href
                        ? { href: action.href(row) }
                        : { onClick: () => run(action) })}
                />
            ))}

            {grouped.length > 0 && (
                <>
                    <ActionIconButton
                        icon={MoreVertIcon}
                        label={__('More actions')}
                        description={__('Shows the other actions of this record.')}
                        aria-haspopup="menu"
                        aria-expanded={anchor !== null}
                        onClick={(event) => setAnchor(event.currentTarget)}
                    />

                    <Menu
                        anchorEl={anchor}
                        open={anchor !== null}
                        onClose={() => setAnchor(null)}
                    >
                        {grouped.map((action) => {
                            const Icon = action.icon;
                            const descriptionId = `${menuId}-${action.key}`;

                            return (
                                <MenuItem
                                    key={action.key}
                                    disabled={action.disabled?.(row) ?? false}
                                    aria-label={action.label}
                                    aria-describedby={descriptionId}
                                    {...(action.href
                                        ? { component: Link, href: action.href(row) }
                                        : { onClick: () => run(action) })}
                                >
                                    <ListItemIcon>
                                        <Icon fontSize="small" color={color(action) === 'error' ? 'error' : 'inherit'} />
                                    </ListItemIcon>

                                    <ListItemText
                                        primary={action.label}
                                        secondary={action.description}
                                        slotProps={{
                                            secondary: { id: descriptionId },
                                        }}
                                    />
                                </MenuItem>
                            );
                        })}
                    </Menu>
                </>
            )}

            <ConfirmDialog
                show={pending !== null}
                title={details?.title ?? ''}
                message={details?.message}
                danger={pending?.variant === 'danger'}
                onConfirm={confirm}
                onClose={() => setPending(null)}
            />
        </Box>
    );
}
