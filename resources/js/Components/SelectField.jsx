import TextField from '@mui/material/TextField';

/**
 * Seletor nativo: mantém o teclado e o toque do navegador.
 */
export default function SelectField({ multiple = false, children, ...props }) {
    return (
        <TextField
            select
            size="small"
            fullWidth
            slotProps={{
                select: { native: true, multiple },
                inputLabel: { shrink: true },
            }}
            {...props}
        >
            {children}
        </TextField>
    );
}
