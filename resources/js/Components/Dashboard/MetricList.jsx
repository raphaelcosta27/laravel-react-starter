import TextLink from '@/Components/TextLink';
import LinearProgress from '@mui/material/LinearProgress';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

/**
 * Quantidades de um bloco. Com endereço, o rótulo leva à lista correspondente. Com
 * `shares`, cada linha mostra a parte do total em uma barra, que só repete o número.
 */
export default function MetricList({ metrics, shares = false }) {
    const total = metrics.reduce((sum, { value }) => sum + value, 0);

    return (
        <Stack
            component="ul"
            spacing={1.5}
            sx={{ listStyle: 'none', m: 0, p: 0 }}
        >
            {metrics.map(({ key, label, value, url }) => (
                <li key={key}>
                    <Stack
                        direction="row"
                        spacing={2}
                        sx={{
                            alignItems: 'baseline',
                            justifyContent: 'space-between',
                        }}
                    >
                        {url ? (
                            <TextLink href={url}>{label}</TextLink>
                        ) : (
                            <Typography variant="body2">{label}</Typography>
                        )}

                        <Typography component="span" sx={{ fontWeight: 600 }}>
                            {value}
                        </Typography>
                    </Stack>

                    {shares && (
                        <LinearProgress
                            aria-hidden="true"
                            variant="determinate"
                            value={total === 0 ? 0 : (value / total) * 100}
                            sx={{ mt: 0.5, height: 6, borderRadius: 3 }}
                        />
                    )}
                </li>
            ))}
        </Stack>
    );
}
