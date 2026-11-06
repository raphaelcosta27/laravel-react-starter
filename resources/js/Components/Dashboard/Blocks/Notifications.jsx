import TextLink from '@/Components/TextLink';
import { useTranslations } from '@/lib/i18n';
import MuiLink from '@mui/material/Link';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

function Title({ title, link }) {
    if (!link) {
        return <Typography variant="body2">{title}</Typography>;
    }

    return link.startsWith('/') ? (
        <TextLink href={link}>{title}</TextLink>
    ) : (
        <MuiLink variant="body2" href={link} rel="noopener noreferrer">
            {title}
        </MuiLink>
    );
}

export const layout = { section: 'account' };

export default function Notifications({ data }) {
    const __ = useTranslations();
    const { unread, items } = data;

    if (items.length === 0) {
        return (
            <Typography variant="body2">
                {__('You have no notifications.')}
            </Typography>
        );
    }

    return (
        <Stack spacing={1.5}>
            <Typography variant="body2">
                {__('Unread: :count', { count: unread })}
            </Typography>

            <Stack
                component="ul"
                spacing={1}
                sx={{ listStyle: 'none', m: 0, p: 0 }}
            >
                {items.map(({ id, title, link, created_at: createdAt }) => (
                    <li key={id}>
                        <Title title={title} link={link} />

                        <Typography
                            variant="caption"
                            sx={{ display: 'block' }}
                            color="text.secondary"
                        >
                            {new Date(createdAt).toLocaleString()}
                        </Typography>
                    </li>
                ))}
            </Stack>
        </Stack>
    );
}
