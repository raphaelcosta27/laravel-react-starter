import ApplicationLogo from '@/Components/ApplicationLogo';
import NotificationBell from '@/Components/NotificationBell';
import PostLink from '@/Components/PostLink';
import StatusAnnouncer from '@/Components/StatusAnnouncer';
import UserAvatar from '@/Components/UserAvatar';
import { useTranslations } from '@/lib/i18n';
import { useCan } from '@/lib/permissions';
import { Link, usePage } from '@inertiajs/react';
import ArrowDropDownIcon from '@mui/icons-material/ArrowDropDown';
import MenuIcon from '@mui/icons-material/Menu';
import AppBar from '@mui/material/AppBar';
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Container from '@mui/material/Container';
import Divider from '@mui/material/Divider';
import Drawer from '@mui/material/Drawer';
import IconButton from '@mui/material/IconButton';
import List from '@mui/material/List';
import ListItem from '@mui/material/ListItem';
import ListItemButton from '@mui/material/ListItemButton';
import ListItemText from '@mui/material/ListItemText';
import Menu from '@mui/material/Menu';
import MenuItem from '@mui/material/MenuItem';
import Paper from '@mui/material/Paper';
import Toolbar from '@mui/material/Toolbar';
import Typography from '@mui/material/Typography';
import { useId, useState } from 'react';

const panelAreas = [
    { label: 'Profiles', route: 'admin.profiles', permission: 'visualizar_perfil' },
    { label: 'Permissions', route: 'admin.permissions', permission: 'visualizar_permissao' },
    { label: 'Users', route: 'admin.users', permission: 'visualizar_usuario' },
];

export default function AuthenticatedLayout({ title, children }) {
    const __ = useTranslations();
    const can = useCan();
    const { user, displayName } = usePage().props.auth;
    const greeting = __('Hello, :name', { name: displayName });
    const accountId = useId();

    const [drawerOpen, setDrawerOpen] = useState(false);
    const [accountAnchor, setAccountAnchor] = useState(null);

    const links = [
        {
            key: 'dashboard',
            label: __('Dashboard'),
            href: route('dashboard'),
            active: route().current('dashboard'),
        },
        ...panelAreas
            .filter((area) => can(area.permission))
            .map((area) => ({
                key: area.route,
                label: __(area.label),
                href: route(`${area.route}.index`),
                active: route().current(`${area.route}.*`),
            })),
    ];

    return (
        <Box sx={{ bgcolor: 'grey.100', minHeight: '100vh' }}>
            <AppBar component="nav" position="static" color="inherit" elevation={1}>
                <Toolbar sx={{ gap: 1 }}>
                    <IconButton
                        edge="start"
                        aria-label={__('Navigation menu')}
                        aria-expanded={drawerOpen}
                        sx={{ display: { sm: 'none' } }}
                        onClick={() => setDrawerOpen(true)}
                    >
                        <MenuIcon />
                    </IconButton>

                    <Link href="/">
                        <ApplicationLogo
                            style={{ display: 'block', height: 36, width: 'auto' }}
                        />
                    </Link>

                    <Box
                        sx={{
                            display: { xs: 'none', sm: 'flex' },
                            gap: 1,
                            marginInlineStart: 2,
                        }}
                    >
                        {links.map((link) => (
                            <Button
                                key={link.key}
                                component={Link}
                                href={link.href}
                                color={link.active ? 'primary' : 'inherit'}
                                aria-current={link.active ? 'page' : undefined}
                                sx={{ textTransform: 'none' }}
                            >
                                {link.label}
                            </Button>
                        ))}
                    </Box>

                    <Box sx={{ marginInlineStart: 'auto' }}>
                        <NotificationBell />
                    </Box>

                    <Box sx={{ display: { xs: 'none', sm: 'block' } }}>
                        <Button
                            id={accountId}
                            color="inherit"
                            aria-haspopup="menu"
                            aria-expanded={accountAnchor !== null}
                            startIcon={<UserAvatar />}
                            endIcon={<ArrowDropDownIcon />}
                            sx={{ textTransform: 'none' }}
                            onClick={(event) => setAccountAnchor(event.currentTarget)}
                        >
                            {greeting}
                        </Button>

                        <Menu
                            anchorEl={accountAnchor}
                            open={accountAnchor !== null}
                            slotProps={{ list: { 'aria-labelledby': accountId } }}
                            onClose={() => setAccountAnchor(null)}
                        >
                            <MenuItem component={Link} href={route('profile.edit')}>
                                {__('Profile')}
                            </MenuItem>
                            <MenuItem component={PostLink} href={route('logout')}>
                                {__('Log Out')}
                            </MenuItem>
                        </Menu>
                    </Box>
                </Toolbar>

                <Drawer
                    open={drawerOpen}
                    ModalProps={{ disablePortal: true, keepMounted: true }}
                    slotProps={{
                        paper: { 'aria-label': __('Navigation menu') },
                    }}
                    onClose={() => setDrawerOpen(false)}
                >
                    <Box sx={{ width: 280 }}>
                        <List>
                            {links.map((link) => (
                                <ListItem key={link.key} disablePadding>
                                    <ListItemButton
                                        component={Link}
                                        href={link.href}
                                        selected={Boolean(link.active)}
                                        aria-current={link.active ? 'page' : undefined}
                                    >
                                        <ListItemText primary={link.label} />
                                    </ListItemButton>
                                </ListItem>
                            ))}
                        </List>

                        <Divider />

                        <Box
                            sx={{
                                display: 'flex',
                                alignItems: 'center',
                                gap: 1.5,
                                px: 2,
                                py: 1.5,
                            }}
                        >
                            <UserAvatar size={40} />

                            <Box sx={{ minWidth: 0 }}>
                                <Typography noWrap>{greeting}</Typography>
                                <Typography
                                    variant="body2"
                                    color="text.secondary"
                                    noWrap
                                >
                                    {user.email}
                                </Typography>
                            </Box>
                        </Box>

                        <List>
                            <ListItem disablePadding>
                                <ListItemButton
                                    component={Link}
                                    href={route('profile.edit')}
                                >
                                    <ListItemText primary={__('Profile')} />
                                </ListItemButton>
                            </ListItem>
                            <ListItem disablePadding>
                                <ListItemButton
                                    component={PostLink}
                                    href={route('logout')}
                                    sx={{ width: '100%' }}
                                >
                                    <ListItemText primary={__('Log Out')} />
                                </ListItemButton>
                            </ListItem>
                        </List>
                    </Box>
                </Drawer>
            </AppBar>

            {title && (
                <Paper component="header" square elevation={0}>
                    <Container maxWidth="xl" sx={{ py: 2.5 }}>
                        <Typography component="h1" variant="h5">
                            {title}
                        </Typography>
                    </Container>
                </Paper>
            )}

            <StatusAnnouncer />

            <Container component="main" maxWidth="xl" sx={{ py: 3 }}>
                {children}
            </Container>
        </Box>
    );
}
