import TextLink from '@/Components/TextLink';
import { useTranslations } from '@/lib/i18n';
import LinearProgress from '@mui/material/LinearProgress';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

export const layout = { section: 'account' };

export default function ProfileCompletion({ data }) {
    const __ = useTranslations();
    const { percent, missing } = data;

    return (
        <Stack spacing={1.5}>
            <Typography variant="h4" component="p" sx={{ fontWeight: 600 }}>
                {`${percent}%`}
            </Typography>

            <LinearProgress
                variant="determinate"
                value={percent}
                aria-label={__('Profile completion')}
                sx={{ height: 8, borderRadius: 4 }}
            />

            {missing.length === 0 ? (
                <Typography variant="body2" color="text.secondary">
                    {__('Your profile is complete.')}
                </Typography>
            ) : (
                <Stack
                    component="ul"
                    spacing={0.5}
                    sx={{ listStyle: 'none', m: 0, p: 0 }}
                >
                    {missing.map(({ key, label, url }) => (
                        <li key={key}>
                            <TextLink href={url}>{label}</TextLink>
                        </li>
                    ))}
                </Stack>
            )}
        </Stack>
    );
}
