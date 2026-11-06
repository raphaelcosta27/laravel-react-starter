import InputAdornment from '@mui/material/InputAdornment';

/** Ícone decorativo no início de um campo: o significado está no rótulo. */
export default function FieldIcon({ icon: Icon }) {
    return (
        <InputAdornment position="start">
            <Icon fontSize="small" color="action" />
        </InputAdornment>
    );
}
