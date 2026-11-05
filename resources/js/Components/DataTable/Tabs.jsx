import Tab from '@mui/material/Tab';
import MuiTabs from '@mui/material/Tabs';

export default function Tabs({ tabs, current, label, onChange }) {
    const known = tabs.some((tab) => tab.key === current);

    return (
        <MuiTabs
            value={known ? current : false}
            aria-label={label}
            variant="scrollable"
            scrollButtons="auto"
            selectionFollowsFocus
            sx={{ borderBottom: 1, borderColor: 'divider' }}
            onChange={(event, key) => onChange(key)}
        >
            {tabs.map((tab) => (
                <Tab
                    key={tab.key}
                    value={tab.key}
                    label={`${tab.label} (${tab.count})`}
                    sx={{ textTransform: 'none' }}
                />
            ))}
        </MuiTabs>
    );
}
