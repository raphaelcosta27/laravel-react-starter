import StatCard from '@/Components/Dashboard/StatCard';
import DeleteOutlinedIcon from '@mui/icons-material/DeleteOutlined';
import MarkEmailUnreadOutlinedIcon from '@mui/icons-material/MarkEmailUnreadOutlined';
import PeopleAltOutlinedIcon from '@mui/icons-material/PeopleAltOutlined';
import PersonOffOutlinedIcon from '@mui/icons-material/PersonOffOutlined';
import Box from '@mui/material/Box';

export const layout = { section: 'overview', size: 'full', plain: true };

const STYLES = {
    active: { icon: PeopleAltOutlinedIcon, color: 'success.dark' },
    inactive: { icon: PersonOffOutlinedIcon, color: 'text.secondary' },
    confirmation_pending: {
        icon: MarkEmailUnreadOutlinedIcon,
        color: 'warning.dark',
    },
    deleted: { icon: DeleteOutlinedIcon, color: 'error.dark' },
};

export default function UsersOverview({ data }) {
    return (
        <Box
            sx={{
                display: 'grid',
                gap: 2,
                gridTemplateColumns: {
                    xs: 'repeat(2, minmax(0, 1fr))',
                    md: 'repeat(4, minmax(0, 1fr))',
                },
            }}
        >
            {data.metrics.map(({ key, label, value, url }) => (
                <StatCard
                    key={key}
                    label={label}
                    value={value}
                    url={url}
                    {...STYLES[key]}
                />
            ))}
        </Box>
    );
}
