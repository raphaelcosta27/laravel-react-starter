import Box from '@mui/material/Box';
import Paper from '@mui/material/Paper';
import Typography from '@mui/material/Typography';

const TOUCH_TARGET = {
    '& .MuiIconButton-root, & .MuiCheckbox-root': {
        minHeight: 44,
        minWidth: 44,
    },
};

/**
 * Mesmas linhas e células da tabela, em outra forma: seleção, interruptor,
 * detalhes e ações continuam sendo os da tela. Os campos valem mesmo para uma
 * coluna oculta no computador.
 */
export default function RowCards({
    rows,
    label,
    busy,
    fields,
    emptyMessage,
    renderCell,
    renderDetail,
    rowSx,
}) {
    if (rows.length === 0) {
        return (
            <Paper
                variant="outlined"
                sx={{ color: 'text.secondary', p: 4, textAlign: 'center' }}
            >
                {emptyMessage}
            </Paper>
        );
    }

    const [title, ...details] = fields;

    return (
        <Box
            component="ul"
            aria-label={label}
            aria-busy={busy}
            sx={{ display: 'grid', gap: 1.5, listStyle: 'none', m: 0, p: 0 }}
        >
            {rows.map((row) => {
                const cells = Object.fromEntries(
                    row.getAllCells().map((cell) => [cell.column.id, cell]),
                );
                const cell = (id) => cells[id] && renderCell(cells[id]);

                return (
                    <Paper
                        key={row.id}
                        component="li"
                        variant="outlined"
                        sx={[
                            TOUCH_TARGET,
                            { minWidth: 0, p: 1.5 },
                            ...[rowSx?.(row.original)].flat(),
                        ]}
                    >
                        <Box sx={{ alignItems: 'center', display: 'flex', gap: 1 }}>
                            {cell('select')}

                            <Typography
                                component="div"
                                variant="subtitle2"
                                sx={{ flexGrow: 1, minWidth: 0, overflowWrap: 'anywhere' }}
                            >
                                {cell(title.id)}
                            </Typography>

                            {cell('expand')}
                        </Box>

                        {details.length > 0 && (
                            <Box component="dl" sx={{ display: 'grid', gap: 0.5, m: 0, mt: 1 }}>
                                {details.map((field) => (
                                    <Box
                                        key={field.id}
                                        sx={{
                                            alignItems: 'center',
                                            display: 'flex',
                                            gap: 1,
                                            justifyContent: 'space-between',
                                        }}
                                    >
                                        <Typography
                                            component="dt"
                                            variant="body2"
                                            sx={{ color: 'text.secondary', flexShrink: 0 }}
                                        >
                                            {field.label}
                                        </Typography>

                                        <Typography
                                            component="dd"
                                            variant="body2"
                                            sx={{ m: 0, minWidth: 0, overflowWrap: 'anywhere', textAlign: 'end' }}
                                        >
                                            {cell(field.id)}
                                        </Typography>
                                    </Box>
                                ))}
                            </Box>
                        )}

                        {cells.actions && (
                            <Box sx={{ display: 'flex', flexWrap: 'wrap', justifyContent: 'flex-end', mt: 1 }}>
                                {cell('actions')}
                            </Box>
                        )}

                        {row.getIsExpanded() && (
                            <Box sx={{ bgcolor: 'action.hover', borderRadius: 1, mt: 1, p: 1.5 }}>
                                {renderDetail(row.original)}
                            </Box>
                        )}
                    </Paper>
                );
            })}
        </Box>
    );
}
