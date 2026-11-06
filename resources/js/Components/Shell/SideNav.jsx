import ApplicationLogo from '@/Components/ApplicationLogo';
import AccountMenu from '@/Components/Shell/AccountMenu';
import { useTranslations } from '@/lib/i18n';
import { useNavigationItems } from '@/lib/useNavigationItems';
import { CONFIG_GROUP } from '@/navigation';
import { Link, usePage } from '@inertiajs/react';
import ExpandLessIcon from '@mui/icons-material/ExpandLess';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import SettingsOutlinedIcon from '@mui/icons-material/SettingsOutlined';
import Box from '@mui/material/Box';
import Collapse from '@mui/material/Collapse';
import List from '@mui/material/List';
import ListItem from '@mui/material/ListItem';
import ListItemButton from '@mui/material/ListItemButton';
import ListItemIcon from '@mui/material/ListItemIcon';
import ListItemText from '@mui/material/ListItemText';
import Tooltip from '@mui/material/Tooltip';
import Typography from '@mui/material/Typography';
import { useId, useState } from 'react';

export const SIDE_NAV_OPEN = 264;
export const SIDE_NAV_COLLAPSED = 76;

const PILL = 999;

function NavItem({ item, collapsed, onNavigate }) {
    const { icon: Icon, label, href, active } = item;

    return (
        <ListItem disablePadding sx={{ mb: 0.5 }}>
            <Tooltip title={collapsed ? label : ''} placement="right">
                <ListItemButton
                    component={Link}
                    href={href}
                    selected={active}
                    aria-current={active ? 'page' : undefined}
                    aria-label={collapsed ? label : undefined}
                    onClick={onNavigate}
                    sx={{
                        borderRadius: `${PILL}px`,
                        justifyContent: collapsed ? 'center' : 'flex-start',
                        minHeight: 44,
                        px: collapsed ? 0 : 1.5,
                    }}
                >
                    <ListItemIcon
                        sx={{
                            color: active ? 'primary.main' : 'text.secondary',
                            justifyContent: 'center',
                            minWidth: collapsed ? 0 : 36,
                        }}
                    >
                        <Icon fontSize="small" />
                    </ListItemIcon>

                    {!collapsed && (
                        <ListItemText
                            primary={label}
                            slotProps={{
                                primary: {
                                    noWrap: true,
                                    variant: 'body2',
                                    sx: { fontWeight: active ? 600 : 400 },
                                },
                            }}
                        />
                    )}
                </ListItemButton>
            </Tooltip>
        </ListItem>
    );
}

/**
 * Fica logo acima da conta. Ancorado no rodapé, cresce para cima ao abrir. Só
 * existe para quem tem algum dos módulos, e já abre quando a tela atual é um deles.
 */
function ConfigMenu({ items, collapsed, onNavigate }) {
    const __ = useTranslations();
    const listId = useId();
    const current = items.some((item) => item.active);
    const [open, setOpen] = useState(current);
    const label = __('Config');
    const Chevron = open ? ExpandMoreIcon : ExpandLessIcon;

    return (
        <Box sx={{ mb: 0.5 }}>
            <Tooltip title={collapsed ? label : ''} placement="right">
                <ListItemButton
                    aria-expanded={open}
                    aria-controls={open ? listId : undefined}
                    aria-label={collapsed ? label : undefined}
                    onClick={() => setOpen((value) => !value)}
                    sx={{
                        borderRadius: `${PILL}px`,
                        justifyContent: collapsed ? 'center' : 'flex-start',
                        minHeight: 44,
                        px: collapsed ? 0 : 1.5,
                    }}
                >
                    <ListItemIcon
                        sx={{
                            color: current ? 'primary.main' : 'text.secondary',
                            justifyContent: 'center',
                            minWidth: collapsed ? 0 : 36,
                        }}
                    >
                        <SettingsOutlinedIcon fontSize="small" />
                    </ListItemIcon>

                    {!collapsed && (
                        <>
                            <ListItemText
                                primary={label}
                                slotProps={{
                                    primary: { noWrap: true, variant: 'body2' },
                                }}
                            />
                            <Chevron
                                fontSize="small"
                                sx={{ color: 'text.secondary' }}
                            />
                        </>
                    )}
                </ListItemButton>
            </Tooltip>

            <Collapse in={open} unmountOnExit>
                <List
                    id={listId}
                    disablePadding
                    aria-label={label}
                    sx={{ mt: 0.5, paddingInlineStart: collapsed ? 0 : 1.5 }}
                >
                    {items.map((item) => (
                        <NavItem
                            key={item.key}
                            item={item}
                            collapsed={collapsed}
                            onNavigate={onNavigate}
                        />
                    ))}
                </List>
            </Collapse>
        </Box>
    );
}

/** Marca, módulos e conta. Serve à lateral fixa e ao menu do celular. */
export function NavContent({ collapsed = false, onNavigate }) {
    const { appName } = usePage().props;
    const items = useNavigationItems();
    const config = items.filter((item) => item.group === CONFIG_GROUP);
    const modules = items.filter((item) => item.group !== CONFIG_GROUP);

    return (
        <>
            <Box
                component={Link}
                href={route('dashboard')}
                onClick={onNavigate}
                sx={{
                    alignItems: 'center',
                    color: 'text.primary',
                    display: 'flex',
                    flexShrink: 0,
                    gap: 1.5,
                    height: 64,
                    justifyContent: collapsed ? 'center' : 'flex-start',
                    px: collapsed ? 0 : 2.5,
                    textDecoration: 'none',
                }}
            >
                <ApplicationLogo
                    alt={collapsed ? appName : ''}
                    style={{ display: 'block', height: 32, width: 32 }}
                />

                {!collapsed && (
                    <Typography noWrap sx={{ fontWeight: 600 }}>
                        {appName}
                    </Typography>
                )}
            </Box>

            <List sx={{ flexGrow: 1, overflowY: 'auto', px: 1.5, py: 1 }}>
                {modules.map((item) => (
                    <NavItem
                        key={item.key}
                        item={item}
                        collapsed={collapsed}
                        onNavigate={onNavigate}
                    />
                ))}
            </List>

            <Box
                sx={{
                    borderBlockStart: 1,
                    borderColor: 'divider',
                    flexShrink: 0,
                    maxHeight: '75%',
                    overflowY: 'auto',
                    p: 1.5,
                }}
            >
                {config.length > 0 && (
                    <ConfigMenu
                        items={config}
                        collapsed={collapsed}
                        onNavigate={onNavigate}
                    />
                )}

                <AccountMenu collapsed={collapsed} onNavigate={onNavigate} />
            </Box>
        </>
    );
}

export default function SideNav({ collapsed = false }) {
    const __ = useTranslations();

    return (
        <Box
            component="nav"
            aria-label={__('Navigation menu')}
            data-print="hide"
            sx={{
                bgcolor: 'background.paper',
                borderInlineEnd: 1,
                borderColor: 'divider',
                display: 'flex',
                flexDirection: 'column',
                flexShrink: 0,
                height: '100dvh',
                overflow: 'hidden',
                position: 'sticky',
                top: 0,
                transition: (theme) =>
                    theme.transitions.create('width', {
                        duration: theme.transitions.duration.shorter,
                    }),
                width: collapsed ? SIDE_NAV_COLLAPSED : SIDE_NAV_OPEN,
            }}
        >
            <NavContent collapsed={collapsed} />
        </Box>
    );
}
