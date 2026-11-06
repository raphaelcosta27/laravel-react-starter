import ActionIconButton from '@/Components/ActionIconButton';
import NotificationBell from '@/Components/NotificationBell';
import { useTranslations } from '@/lib/i18n';
import MenuIcon from '@mui/icons-material/Menu';
import MenuOpenIcon from '@mui/icons-material/MenuOpen';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';

export default function TopBar({
    title,
    collapsed,
    onToggle,
    menuOpen = false,
    onOpenMenu,
}) {
    const __ = useTranslations();

    return (
        <Box
            component="header"
            data-print="hide"
            sx={{
                alignItems: 'center',
                bgcolor: 'background.default',
                borderBlockEnd: 1,
                borderColor: 'divider',
                display: 'flex',
                flexShrink: 0,
                gap: 1,
                minHeight: { xs: 56, sm: 64 },
                paddingBlockStart: 'env(safe-area-inset-top)',
                position: 'sticky',
                px: { xs: 1, sm: 3 },
                top: 0,
                zIndex: 'appBar',
            }}
        >
            {onOpenMenu && (
                <ActionIconButton
                    icon={MenuIcon}
                    label={__('Open menu')}
                    aria-haspopup="dialog"
                    aria-expanded={menuOpen}
                    onClick={onOpenMenu}
                />
            )}

            {onToggle && (
                <ActionIconButton
                    icon={collapsed ? MenuIcon : MenuOpenIcon}
                    label={__(collapsed ? 'Expand menu' : 'Collapse menu')}
                    aria-expanded={!collapsed}
                    onClick={onToggle}
                />
            )}

            {/* No celular, o título visível fica aqui. A tela mantém o cabeçalho oculto. */}
            <Typography
                noWrap
                aria-hidden="true"
                sx={{ display: { sm: 'none' }, fontWeight: 600 }}
            >
                {title}
            </Typography>

            <Box sx={{ marginInlineStart: 'auto' }}>
                <NotificationBell />
            </Box>
        </Box>
    );
}
