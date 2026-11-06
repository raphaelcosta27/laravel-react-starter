import { useTranslations } from '@/lib/i18n';
import Alert from '@mui/material/Alert';
import Paper from '@mui/material/Paper';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import { useId } from 'react';

export default function BlockCard({ title, failed = false, children }) {
    const __ = useTranslations();
    const titleId = useId();

    return (
        <Paper
            component="section"
            variant="outlined"
            aria-labelledby={titleId}
            sx={{ p: 2.5, borderRadius: 2 }}
        >
            <Stack spacing={2}>
                <Typography
                    id={titleId}
                    component="h3"
                    variant="subtitle1"
                    sx={{ fontWeight: 600 }}
                >
                    {title}
                </Typography>

                {failed ? (
                    <Alert severity="warning">
                        {__('This block could not be loaded.')}
                    </Alert>
                ) : (
                    children
                )}
            </Stack>
        </Paper>
    );
}
