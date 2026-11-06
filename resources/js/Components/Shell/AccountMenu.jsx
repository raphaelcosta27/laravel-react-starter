import PostLink from '@/Components/PostLink';
import ThemeSwitcher from '@/Components/Shell/ThemeSwitcher';
import UserAvatar from '@/Components/UserAvatar';
import { useTranslations } from '@/lib/i18n';
import { Link, usePage } from '@inertiajs/react';
import LogoutIcon from '@mui/icons-material/Logout';
import PersonOutlinedIcon from '@mui/icons-material/PersonOutlined';
import UnfoldMoreIcon from '@mui/icons-material/UnfoldMore';
import Box from '@mui/material/Box';
import ButtonBase from '@mui/material/ButtonBase';
import Divider from '@mui/material/Divider';
import ListItemIcon from '@mui/material/ListItemIcon';
import Menu from '@mui/material/Menu';
import MenuItem from '@mui/material/MenuItem';
import Typography from '@mui/material/Typography';
import { useId, useState } from 'react';

export default function AccountMenu({ collapsed = false, onNavigate }) {
    const __ = useTranslations();
    const { user, displayName } = usePage().props.auth;
    const buttonId = useId();
    const [anchor, setAnchor] = useState(null);

    const leave = () => {
        setAnchor(null);
        onNavigate?.();
    };

    return (
        <>
            <ButtonBase
                id={buttonId}
                aria-label={__('Hello, :name', { name: displayName })}
                aria-haspopup="menu"
                aria-expanded={anchor !== null}
                onClick={(event) => setAnchor(event.currentTarget)}
                sx={{
                    alignItems: 'center',
                    borderRadius: 1,
                    display: 'flex',
                    gap: 1.25,
                    justifyContent: collapsed ? 'center' : 'flex-start',
                    minHeight: 44,
                    p: collapsed ? 0.5 : 1,
                    textAlign: 'start',
                    width: '100%',
                    '&:hover': { bgcolor: 'action.hover' },
                }}
            >
                <UserAvatar size={36} />

                {!collapsed && (
                    <>
                        <Box sx={{ flexGrow: 1, minWidth: 0 }}>
                            <Typography
                                noWrap
                                variant="body2"
                                sx={{ fontWeight: 600 }}
                            >
                                {displayName}
                            </Typography>
                            <Typography
                                noWrap
                                variant="caption"
                                component="p"
                                sx={{ color: 'text.secondary' }}
                            >
                                {user.email}
                            </Typography>
                        </Box>

                        <UnfoldMoreIcon
                            fontSize="small"
                            sx={{ color: 'text.secondary' }}
                        />
                    </>
                )}
            </ButtonBase>

            <Menu
                anchorEl={anchor}
                open={anchor !== null}
                anchorOrigin={{ horizontal: 'left', vertical: 'top' }}
                transformOrigin={{ horizontal: 'left', vertical: 'bottom' }}
                slotProps={{
                    list: { 'aria-labelledby': buttonId },
                    paper: { sx: { minWidth: 232 } },
                }}
                onClose={() => setAnchor(null)}
            >
                <MenuItem
                    component={Link}
                    href={route('profile.edit')}
                    onClick={leave}
                >
                    <ListItemIcon>
                        <PersonOutlinedIcon fontSize="small" />
                    </ListItemIcon>
                    {__('Profile')}
                </MenuItem>

                <Divider />

                <ThemeSwitcher />

                <Divider />

                <MenuItem
                    component={PostLink}
                    href={route('logout')}
                    sx={{ width: '100%' }}
                    onClick={leave}
                >
                    <ListItemIcon>
                        <LogoutIcon fontSize="small" />
                    </ListItemIcon>
                    {__('Log Out')}
                </MenuItem>
            </Menu>
        </>
    );
}
