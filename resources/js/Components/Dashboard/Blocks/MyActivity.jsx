import { useTranslations } from '@/lib/i18n';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

export const layout = { section: 'account' };

export default function MyActivity({ data }) {
    const __ = useTranslations();

    if (data.items.length === 0) {
        return (
            <Typography variant="body2">
                {__('No recent activity.')}
            </Typography>
        );
    }

    return (
        <Stack
            component="ul"
            spacing={1}
            sx={{ listStyle: 'none', m: 0, p: 0 }}
        >
            {data.items.map(({ entity, action, fields, created_at: createdAt }) => (
                <li key={`${createdAt}-${entity}-${fields.join()}`}>
                    <Typography variant="body2">
                        {`${entity} · ${action}`}
                    </Typography>

                    <Typography
                        variant="caption"
                        sx={{ display: 'block' }}
                        color="text.secondary"
                    >
                        {[...fields, new Date(createdAt).toLocaleString()].join(' · ')}
                    </Typography>
                </li>
            ))}
        </Stack>
    );
}
