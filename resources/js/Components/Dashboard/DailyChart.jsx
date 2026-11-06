import { useTranslations } from '@/lib/i18n';
import Box from '@mui/material/Box';
import Skeleton from '@mui/material/Skeleton';
import Stack from '@mui/material/Stack';
import Table from '@mui/material/Table';
import TableBody from '@mui/material/TableBody';
import TableCell from '@mui/material/TableCell';
import TableHead from '@mui/material/TableHead';
import TableRow from '@mui/material/TableRow';
import Typography from '@mui/material/Typography';
import { lazy, Suspense } from 'react';

const HEIGHT = 240;

// A biblioteca de gráficos só é baixada por quem vê um gráfico.
const DailyChartPlot = lazy(() => import('./DailyChartPlot'));

export const shortDate = (date) =>
    date.split('-').reverse().slice(0, 2).join('/');

/**
 * O gráfico é uma leitura rápida: quem não o vê tem a tabela com os mesmos valores
 * logo abaixo.
 */
export default function DailyChart({ label, days, series, total, type }) {
    const __ = useTranslations();

    if (total === 0) {
        return (
            <Typography variant="body2">
                {__('No data in this period.')}
            </Typography>
        );
    }

    return (
        <Stack spacing={1}>
            <div>
                <Typography variant="h4" component="p" sx={{ fontWeight: 600 }}>
                    {total}
                </Typography>

                <Typography variant="caption" color="text.secondary">
                    {__('In the last :days days', { days })}
                </Typography>
            </div>

            <Box aria-hidden="true" sx={{ height: HEIGHT }}>
                <Suspense
                    fallback={<Skeleton variant="rounded" height={HEIGHT} />}
                >
                    <DailyChartPlot
                        label={label}
                        series={series}
                        type={type}
                        height={HEIGHT}
                    />
                </Suspense>
            </Box>

            <Box component="details">
                <Typography
                    component="summary"
                    variant="body2"
                    sx={{ cursor: 'pointer' }}
                >
                    {__('See the values')}
                </Typography>

                <Table size="small" aria-label={label}>
                    <TableHead>
                        <TableRow>
                            <TableCell>{__('Date')}</TableCell>
                            <TableCell align="right">{__('Quantity')}</TableCell>
                        </TableRow>
                    </TableHead>

                    <TableBody>
                        {series.map(({ date, value }) => (
                            <TableRow key={date}>
                                <TableCell>{shortDate(date)}</TableCell>
                                <TableCell align="right">{value}</TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </Box>
        </Stack>
    );
}
