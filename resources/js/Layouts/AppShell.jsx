import SideNav, { NavContent, SIDE_NAV_OPEN } from '@/Components/Shell/SideNav';
import TopBar from '@/Components/Shell/TopBar';
import StatusAnnouncer from '@/Components/StatusAnnouncer';
import { useTranslations } from '@/lib/i18n';
import { useBackClose } from '@/lib/useBackClose';
import { useShortcuts } from '@/lib/useShortcuts';
import { visuallyHidden } from '@/lib/visuallyHidden';
import { usePage } from '@inertiajs/react';
import Box from '@mui/material/Box';
import Drawer from '@mui/material/Drawer';
import { useColorScheme, useTheme } from '@mui/material/styles';
import useMediaQuery from '@mui/material/useMediaQuery';
import { createContext, useContext, useEffect, useState } from 'react';

const COLLAPSED_KEY = 'shell:collapsed';

const ShellTitle = createContext(() => {});

/** Só muda de tela com um efeito curto: filtrar e paginar não animam. */
const ENTRANCE = {
    animation: 'shell-enter 160ms ease-out',
    '@keyframes shell-enter': {
        from: { opacity: 0, transform: 'translateY(4px)' },
        to: { opacity: 1, transform: 'none' },
    },
};

/** O armazenamento pode faltar, e isso não pode derrubar o menu. */
const storedCollapsed = () => {
    try {
        return window.localStorage.getItem(COLLAPSED_KEY) === 'true';
    } catch {
        return false;
    }
};

const storeCollapsed = (value) => {
    try {
        window.localStorage.setItem(COLLAPSED_KEY, String(value));
    } catch {
        // A preferência dura até recarregar a tela.
    }
};

/** Leva o título da tela à barra superior. */
export function useShellTitle(title) {
    const setTitle = useContext(ShellTitle);

    useEffect(() => {
        setTitle(title ?? '');
    }, [setTitle, title]);
}

/** Ao entrar, a escolha da conta vale sobre a do aparelho. */
function useAccountTheme() {
    const { theme } = usePage().props.auth;
    const { setMode } = useColorScheme();

    useEffect(() => {
        if (theme) {
            setMode(theme);
        }
    }, [setMode, theme]);
}

/**
 * Estrutura das telas internas. Fica montada entre as trocas de tela: só o
 * conteúdo muda, e o menu mantém posição e estado.
 */
export default function AppShell({ children }) {
    const __ = useTranslations();
    const { component } = usePage();
    const theme = useTheme();
    const narrow = useMediaQuery(theme.breakpoints.down('lg'), { noSsr: true });
    const phone = useMediaQuery(theme.breakpoints.down('sm'), { noSsr: true });

    const [title, setTitle] = useState('');
    const [collapsed, setCollapsed] = useState(storedCollapsed);
    const [menuOpen, setMenuOpen] = useState(false);

    const closeMenu = () => setMenuOpen(false);

    useAccountTheme();
    useBackClose(phone && menuOpen, closeMenu);

    const toggle = () => {
        if (narrow) {
            return;
        }

        setCollapsed((current) => {
            storeCollapsed(!current);

            return !current;
        });
    };

    useShortcuts({ b: toggle });

    return (
        <ShellTitle.Provider value={setTitle}>
            <Box
                component="a"
                href="#content"
                sx={{
                    ...visuallyHidden,
                    '&:focus': {
                        bgcolor: 'background.paper',
                        clip: 'auto',
                        height: 'auto',
                        insetBlockStart: 8,
                        insetInlineStart: 8,
                        p: 1,
                        position: 'fixed',
                        width: 'auto',
                        zIndex: 'tooltip',
                    },
                }}
            >
                {__('Skip to content')}
            </Box>

            <Box
                sx={{
                    bgcolor: 'background.default',
                    display: 'flex',
                    minHeight: '100dvh',
                }}
            >
                {phone ? (
                    <Drawer
                        open={menuOpen}
                        slotProps={{
                            paper: {
                                'aria-label': __('Navigation menu'),
                                sx: {
                                    maxWidth: '85vw',
                                    paddingBlock:
                                        'env(safe-area-inset-top) env(safe-area-inset-bottom)',
                                    width: SIDE_NAV_OPEN,
                                },
                            },
                        }}
                        onClose={closeMenu}
                    >
                        <Box
                            component="nav"
                            aria-label={__('Navigation menu')}
                            sx={{
                                display: 'flex',
                                flexDirection: 'column',
                                flexGrow: 1,
                                minHeight: 0,
                            }}
                        >
                            <NavContent onNavigate={closeMenu} />
                        </Box>
                    </Drawer>
                ) : (
                    <SideNav collapsed={narrow || collapsed} />
                )}

                <Box
                    sx={{
                        display: 'flex',
                        flexDirection: 'column',
                        flexGrow: 1,
                        minWidth: 0,
                    }}
                >
                    <TopBar
                        title={title}
                        collapsed={collapsed}
                        onToggle={narrow ? undefined : toggle}
                        menuOpen={menuOpen}
                        onOpenMenu={phone ? () => setMenuOpen(true) : undefined}
                    />

                    <StatusAnnouncer />

                    <Box key={component} sx={ENTRANCE}>
                        {children}
                    </Box>
                </Box>
            </Box>
        </ShellTitle.Provider>
    );
}
