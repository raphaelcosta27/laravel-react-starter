import { Link } from '@inertiajs/react';
import Box from '@mui/material/Box';
import CardActionArea from '@mui/material/CardActionArea';
import Paper from '@mui/material/Paper';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

/** Indicador em destaque. Com endereço, o cartão inteiro leva à lista. */
export default function StatCard({ label, value, url, icon: Icon, color }) {
    const content = (
        <Stack
            direction="row"
            spacing={2}
            sx={{
                p: 2.5,
                alignItems: 'flex-start',
                justifyContent: 'space-between',
            }}
        >
            <div>
                <Typography
                    variant="subtitle2"
                    component="p"
                    color="text.secondary"
                >
                    {label}
                </Typography>

                <Typography
                    variant="h4"
                    component="p"
                    sx={{ fontWeight: 600, mt: 0.5 }}
                >
                    {value}
                </Typography>
            </div>

            <Box
                sx={{
                    display: 'flex',
                    p: 1,
                    borderRadius: 2,
                    color,
                    bgcolor: 'action.hover',
                }}
            >
                <Icon />
            </Box>
        </Stack>
    );

    return (
        <Paper variant="outlined" sx={{ borderRadius: 2, overflow: 'hidden' }}>
            {url ? (
                <CardActionArea component={Link} href={url}>
                    {content}
                </CardActionArea>
            ) : (
                content
            )}
        </Paper>
    );
}
