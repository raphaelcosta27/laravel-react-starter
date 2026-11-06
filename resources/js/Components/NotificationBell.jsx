import ActionIconButton from '@/Components/ActionIconButton';
import { requestJson } from '@/lib/http';
import { useTranslations } from '@/lib/i18n';
import { visuallyHidden } from '@/lib/visuallyHidden';
import { Link, usePage } from '@inertiajs/react';
import CheckCircleOutlinedIcon from '@mui/icons-material/CheckCircleOutlined';
import CloseIcon from '@mui/icons-material/Close';
import ErrorOutlinedIcon from '@mui/icons-material/ErrorOutlined';
import InfoOutlinedIcon from '@mui/icons-material/InfoOutlined';
import NotificationsNoneIcon from '@mui/icons-material/NotificationsNone';
import WarningAmberIcon from '@mui/icons-material/WarningAmber';
import Badge from '@mui/material/Badge';
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import CircularProgress from '@mui/material/CircularProgress';
import Drawer from '@mui/material/Drawer';
import IconButton from '@mui/material/IconButton';
import Stack from '@mui/material/Stack';
import Tooltip from '@mui/material/Tooltip';
import Typography from '@mui/material/Typography';
import { useEffect, useId, useRef, useState } from 'react';

const TYPES = {
    info: { icon: InfoOutlinedIcon, label: 'Information', color: 'info.main' },
    success: {
        icon: CheckCircleOutlinedIcon,
        label: 'Success',
        color: 'success.main',
    },
    warning: { icon: WarningAmberIcon, label: 'Warning', color: 'warning.dark' },
    error: { icon: ErrorOutlinedIcon, label: 'Error', color: 'error.main' },
};

const badgeText = (count) => (count > 99 ? '99+' : count);

export default function NotificationBell() {
    const __ = useTranslations();
    const shared = usePage().props.notifications;
    const titleId = useId();
    const closeButton = useRef(null);

    const [unread, setUnread] = useState(shared?.unread ?? 0);
    const [open, setOpen] = useState(false);
    const [items, setItems] = useState([]);
    const [nextBefore, setNextBefore] = useState(null);
    const [loading, setLoading] = useState(false);
    const [failed, setFailed] = useState(false);

    const sharedUnread = shared?.unread;
    const pollSeconds = shared?.pollSeconds;

    useEffect(() => {
        if (sharedUnread !== undefined) {
            setUnread(sharedUnread);
        }
    }, [sharedUnread]);

    useEffect(() => {
        if (!pollSeconds) {
            return undefined;
        }

        const timer = setInterval(() => {
            if (!document.hidden) {
                requestJson(route('notifications.unread'))
                    .then((response) => setUnread(response.unread))
                    .catch(() => {});
            }
        }, pollSeconds * 1000);

        return () => clearInterval(timer);
    }, [pollSeconds]);

    if (!shared) {
        return null;
    }

    /** O que aparece na barra conta como visto; o destaque fica até reabrir. */
    const load = async (before = null) => {
        setLoading(true);
        setFailed(false);

        try {
            const response = await requestJson(
                route('notifications.index') +
                    (before ? `?before=${before}` : ''),
            );
            const fresh = response.data.filter(({ is_new }) => is_new);

            setItems((current) =>
                before ? [...current, ...response.data] : response.data,
            );
            setNextBefore(response.next_before);
            setUnread(response.unread);

            if (fresh.length > 0) {
                const seen = await requestJson(route('notifications.seen'), {
                    method: 'POST',
                    body: { ids: fresh.map(({ id }) => id) },
                });

                setUnread(seen.unread);
            }
        } catch {
            setFailed(true);
        } finally {
            setLoading(false);
        }
    };

    const show = () => {
        setItems([]);
        setNextBefore(null);
        setOpen(true);
        load();
    };

    const hide = async (notification) => {
        try {
            const response = await requestJson(
                route('notifications.destroy', notification.id),
                { method: 'DELETE' },
            );

            setItems((current) =>
                current.filter(({ id }) => id !== notification.id),
            );
            setUnread(response.unread);
            closeButton.current?.focus();
        } catch {
            setFailed(true);
        }
    };

    const label =
        unread === 0
            ? __('Notifications: none new')
            : unread === 1
              ? __('Notifications: 1 new')
              : __('Notifications: :count new', { count: badgeText(unread) });

    return (
        <>
            <Tooltip title={label}>
                <IconButton color="inherit" aria-label={label} onClick={show}>
                    <Badge badgeContent={badgeText(unread)} color="error">
                        <NotificationsNoneIcon />
                    </Badge>
                </IconButton>
            </Tooltip>

            <span aria-live="polite" style={visuallyHidden}>
                {unread > 0 ? label : ''}
            </span>

            <Drawer
                anchor="right"
                open={open}
                onClose={() => setOpen(false)}
                slotProps={{
                    paper: {
                        role: 'dialog',
                        'aria-labelledby': titleId,
                        sx: { width: { xs: '100%', sm: 400 } },
                    },
                }}
            >
                <Stack
                    direction="row"
                    sx={{
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        p: 2,
                    }}
                >
                    <Typography id={titleId} component="h2" variant="h6">
                        {__('Notifications')}
                    </Typography>

                    <IconButton
                        ref={closeButton}
                        aria-label={__('Close notifications')}
                        onClick={() => setOpen(false)}
                    >
                        <CloseIcon />
                    </IconButton>
                </Stack>

                <Box sx={{ overflowY: 'auto', px: 2, pb: 2 }}>
                    {failed && (
                        <Typography role="alert" variant="body2" color="error">
                            {__('The notifications could not be loaded. Try again.')}
                        </Typography>
                    )}

                    {!loading && !failed && items.length === 0 && (
                        <Typography variant="body2">
                            {__('You have no notifications.')}
                        </Typography>
                    )}

                    <Stack
                        component="ul"
                        spacing={2}
                        sx={{ listStyle: 'none', m: 0, p: 0 }}
                    >
                        {items.map((item) => (
                            <NotificationItem
                                key={item.id}
                                item={item}
                                onHide={() => hide(item)}
                                onFollow={() => setOpen(false)}
                            />
                        ))}
                    </Stack>

                    {loading && (
                        <Box sx={{ textAlign: 'center', mt: 2 }}>
                            <CircularProgress
                                size={24}
                                aria-label={__('Loading notifications')}
                            />
                        </Box>
                    )}

                    {!loading && nextBefore !== null && (
                        <Button
                            fullWidth
                            sx={{ mt: 2 }}
                            onClick={() => load(nextBefore)}
                        >
                            {__('Load more')}
                        </Button>
                    )}
                </Box>
            </Drawer>
        </>
    );
}

function NotificationItem({ item, onHide, onFollow }) {
    const __ = useTranslations();
    const type = TYPES[item.type] ?? TYPES.info;
    const Icon = type.icon;
    const internal = item.link?.startsWith('/');

    return (
        <Stack
            component="li"
            direction="row"
            spacing={1}
            sx={{
                alignItems: 'flex-start',
                borderInlineStart: 3,
                borderColor: item.is_new ? 'primary.main' : 'divider',
                pl: 1,
            }}
        >
            <Icon fontSize="small" sx={{ color: type.color, mt: 0.25 }} />

            <Box sx={{ flexGrow: 1, minWidth: 0 }}>
                <Typography
                    variant="body2"
                    sx={{
                        fontWeight: item.is_new ? 700 : 500,
                        overflowWrap: 'anywhere',
                    }}
                >
                    {item.title}
                </Typography>

                <Typography variant="body2" sx={{ overflowWrap: 'anywhere' }}>
                    {item.body}
                </Typography>

                <Typography variant="caption" color="text.secondary" component="p">
                    {[
                        ...(item.is_new ? [__('New')] : []),
                        __(type.label),
                        new Date(item.created_at).toLocaleString(),
                    ].join(' · ')}
                </Typography>

                {item.link && (
                    <Typography variant="body2">
                        {internal ? (
                            <Link href={item.link} onClick={onFollow}>
                                {__('Open')}
                            </Link>
                        ) : (
                            <a href={item.link} rel="noopener noreferrer">
                                {__('Open')}
                            </a>
                        )}
                    </Typography>
                )}
            </Box>

            <ActionIconButton
                icon={CloseIcon}
                label={__('Hide notification')}
                description={__('Remove this notification from your list.')}
                onClick={onHide}
            />
        </Stack>
    );
}
