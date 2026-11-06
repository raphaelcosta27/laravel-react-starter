import TextField from '@mui/material/TextField';

/**
 * Seletor nativo: mantém o teclado e o toque do navegador. O obrigatório vai ao
 * campo nativo, sem asterisco no rótulo.
 */
export default function SelectField({
    multiple = false,
    required = false,
    autoFocus = false,
    children,
    ...props
}) {
    return (
        <TextField
            select
            size="small"
            fullWidth
            slotProps={{
                select: { native: true, multiple, required, autoFocus },
                inputLabel: { shrink: true },
            }}
            {...props}
        >
            {children}
        </TextField>
    );
}
