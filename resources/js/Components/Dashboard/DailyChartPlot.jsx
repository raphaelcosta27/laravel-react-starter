import { useTheme } from '@mui/material/styles';
import { BarChart } from '@mui/x-charts/BarChart';
import { LineChart } from '@mui/x-charts/LineChart';
import { useId } from 'react';
import { shortDate } from './DailyChart';

/** Em períodos longos, mostrar todo dia no eixo embaralha os rótulos. */
const everyNth = (length) => {
    const step = Math.max(1, Math.ceil(length / 7));

    return (value, index) => (length - 1 - index) % step === 0;
};

export default function DailyChartPlot({ label, series, type, height }) {
    const theme = useTheme();
    const gradientId = useId().replace(/\W/g, '');

    const common = {
        height,
        hideLegend: true,
        grid: { horizontal: true },
        margin: { left: 0, right: 16, top: 16, bottom: 0 },
        colors: [theme.palette.primary.main],
        yAxis: [
            { width: 32, tickMinStep: 1, disableLine: true, disableTicks: true },
        ],
    };

    const dates = series.map(({ date }) => shortDate(date));
    const values = series.map(({ value }) => value);
    const axis = {
        data: dates,
        tickLabelInterval: everyNth(dates.length),
        disableTicks: true,
    };

    if (type === 'bar') {
        return (
            <BarChart
                {...common}
                borderRadius={4}
                xAxis={[{ ...axis, scaleType: 'band', categoryGapRatio: 0.5 }]}
                series={[{ data: values, label }]}
            />
        );
    }

    return (
        <LineChart
            {...common}
            xAxis={[{ ...axis, scaleType: 'point' }]}
            series={[
                {
                    data: values,
                    label,
                    area: true,
                    curve: 'linear',
                    showMark: false,
                },
            ]}
            sx={{ '& .MuiLineChart-area': { fill: `url(#${gradientId})` } }}
        >
            <defs>
                <linearGradient id={gradientId} x1="0" x2="0" y1="0" y2="1">
                    <stop
                        offset="0%"
                        stopColor={theme.palette.primary.main}
                        stopOpacity={0.45}
                    />
                    <stop
                        offset="100%"
                        stopColor={theme.palette.primary.main}
                        stopOpacity={0.08}
                    />
                </linearGradient>
            </defs>
        </LineChart>
    );
}
