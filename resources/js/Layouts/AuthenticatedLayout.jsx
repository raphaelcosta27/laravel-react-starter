import { useShellTitle } from '@/Layouts/AppShell';
import { visuallyHidden } from '@/lib/visuallyHidden';
import Box from '@mui/material/Box';
import Container from '@mui/material/Container';
import Typography from '@mui/material/Typography';

/** Conteúdo da tela. Menus e barras ficam no `AppShell`, que não desmonta. */
export default function AuthenticatedLayout({ title, actions, children }) {
    useShellTitle(title);

    return (
        <Container
            component="main"
            id="content"
            tabIndex={-1}
            maxWidth="xl"
            sx={{
                outline: 'none',
                paddingBlockEnd: 'calc(24px + env(safe-area-inset-bottom))',
                paddingBlockStart: { xs: 2, sm: 3 },
            }}
        >
            {(title || actions) && (
                <Box
                    sx={{
                        alignItems: 'center',
                        display: 'flex',
                        flexWrap: 'wrap',
                        gap: 2,
                        justifyContent: 'space-between',
                        mb: { xs: actions ? 2 : 0, sm: 3 },
                    }}
                >
                    {title && (
                        <Typography
                            component="h1"
                            variant="h5"
                            sx={(theme) => ({
                                [theme.breakpoints.down('sm')]: visuallyHidden,
                            })}
                        >
                            {title}
                        </Typography>
                    )}

                    {actions && (
                        <Box sx={{ display: 'flex', gap: 1 }}>{actions}</Box>
                    )}
                </Box>
            )}

            {children}
        </Container>
    );
}
