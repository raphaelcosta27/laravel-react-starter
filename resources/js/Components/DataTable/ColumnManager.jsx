import { useTranslations } from '@/lib/i18n';
import AddIcon from '@mui/icons-material/Add';
import ArrowBackIcon from '@mui/icons-material/ArrowBack';
import ArrowForwardIcon from '@mui/icons-material/ArrowForward';
import RemoveIcon from '@mui/icons-material/Remove';
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Checkbox from '@mui/material/Checkbox';
import FormControlLabel from '@mui/material/FormControlLabel';
import IconButton from '@mui/material/IconButton';
import Paper from '@mui/material/Paper';
import { useState } from 'react';

const DEFAULT_WIDTH = 150;
const MIN_WIDTH = 60;
const STEP = 20;

/**
 * Botões como alternativa ao arraste, que funcionam pelo teclado (WCAG 2.5.7).
 */
export default function ColumnManager({
    columns,
    order,
    visibility,
    sizing,
    onVisibilityChange,
    onOrderChange,
    onSizeChange,
}) {
    const __ = useTranslations();
    const [open, setOpen] = useState(false);

    const position = (id) => order.indexOf(id);

    const ordered = [...columns].sort((a, b) => position(a.id) - position(b.id));

    const move = (id, offset) => {
        const next = [...order];
        const from = next.indexOf(id);

        next.splice(from, 1);
        next.splice(from + offset, 0, id);

        onOrderChange(next);
    };

    const resize = (id, change) =>
        onSizeChange({
            ...sizing,
            [id]: Math.max(MIN_WIDTH, (sizing[id] ?? DEFAULT_WIDTH) + change),
        });

    return (
        <Box sx={{ position: 'relative' }}>
            <Button
                variant="outlined"
                size="small"
                aria-expanded={open}
                onClick={() => setOpen((value) => !value)}
            >
                {__('Columns')}
            </Button>

            {open && (
                <Paper
                    component="ul"
                    elevation={4}
                    sx={{
                        insetInlineEnd: 0,
                        listStyle: 'none',
                        m: 0,
                        maxWidth: 'calc(100vw - 2rem)',
                        mt: 1,
                        p: 1.5,
                        position: 'absolute',
                        width: 340,
                        zIndex: 20,
                    }}
                >
                    {ordered.map((column) => (
                        <Box
                            component="li"
                            key={column.id}
                            sx={{
                                alignItems: 'center',
                                display: 'flex',
                                gap: 1,
                                justifyContent: 'space-between',
                            }}
                        >
                            <FormControlLabel
                                label={column.label}
                                slotProps={{ typography: { variant: 'body2' } }}
                                control={
                                    <Checkbox
                                        size="small"
                                        checked={visibility[column.id] !== false}
                                        disabled={column.required}
                                        onChange={(event) =>
                                            onVisibilityChange({
                                                ...visibility,
                                                [column.id]: event.target.checked,
                                            })
                                        }
                                    />
                                }
                            />

                            <Box sx={{ display: 'flex', flexShrink: 0 }}>
                                <IconButton
                                    size="small"
                                    aria-label={__('Move :column left', { column: column.label })}
                                    disabled={position(column.id) <= 0}
                                    onClick={() => move(column.id, -1)}
                                >
                                    <ArrowBackIcon fontSize="inherit" />
                                </IconButton>
                                <IconButton
                                    size="small"
                                    aria-label={__('Move :column right', { column: column.label })}
                                    disabled={position(column.id) >= order.length - 1}
                                    onClick={() => move(column.id, 1)}
                                >
                                    <ArrowForwardIcon fontSize="inherit" />
                                </IconButton>
                                <IconButton
                                    size="small"
                                    aria-label={__('Decrease width of :column', { column: column.label })}
                                    onClick={() => resize(column.id, -STEP)}
                                >
                                    <RemoveIcon fontSize="inherit" />
                                </IconButton>
                                <IconButton
                                    size="small"
                                    aria-label={__('Increase width of :column', { column: column.label })}
                                    onClick={() => resize(column.id, STEP)}
                                >
                                    <AddIcon fontSize="inherit" />
                                </IconButton>
                            </Box>
                        </Box>
                    ))}
                </Paper>
            )}
        </Box>
    );
}
