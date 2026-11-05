import Switch from '@mui/material/Switch';

export default function ToggleCell({ checked, label, disabled = false, onChange }) {
    return (
        <Switch
            size="small"
            checked={checked}
            disabled={disabled}
            slotProps={{ input: { 'aria-label': label } }}
            onChange={(event) => onChange(event.target.checked)}
        />
    );
}
