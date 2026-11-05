import ColumnFilter from '@/Components/DataTable/ColumnFilter';
import { useTranslations } from '@/lib/i18n';
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Paper from '@mui/material/Paper';
import Stack from '@mui/material/Stack';
import TextField from '@mui/material/TextField';
import { useState } from 'react';

const isEmpty = (value) =>
    value === '' ||
    value === null ||
    value === undefined ||
    (Array.isArray(value) && value.length === 0) ||
    (typeof value === 'object' &&
        !Array.isArray(value) &&
        Object.values(value).every((item) => item === '' || item === undefined));

export default function Toolbar({
    filters,
    query,
    onSearch,
    onFilter,
    onRestore,
    children,
}) {
    const __ = useTranslations();
    const [term, setTerm] = useState(query.search ?? '');
    const [open, setOpen] = useState(false);
    const [draft, setDraft] = useState(query.filter ?? {});

    const activeFilters = Object.keys(query.filter ?? {}).length;

    const applyFilters = (event) => {
        event.preventDefault();

        onFilter(
            Object.fromEntries(
                Object.entries(draft).filter(([, value]) => !isEmpty(value)),
            ),
        );
    };

    const clearFilters = () => {
        setDraft({});
        onFilter({});
    };

    return (
        <Stack spacing={1.5} sx={{ py: 1.5 }}>
            <Stack
                direction="row"
                useFlexGap
                spacing={1}
                sx={{ alignItems: 'center', flexWrap: 'wrap' }}
            >
                <Stack
                    component="form"
                    role="search"
                    direction="row"
                    spacing={1}
                    sx={{ alignItems: 'center' }}
                    onSubmit={(event) => {
                        event.preventDefault();
                        onSearch(term);
                    }}
                >
                    <TextField
                        id="table-search"
                        type="search"
                        size="small"
                        label={__('Search')}
                        value={term}
                        onChange={(event) => setTerm(event.target.value)}
                    />
                    <Button type="submit" variant="outlined" size="small">
                        {__('Search')}
                    </Button>
                </Stack>

                {filters.length > 0 && (
                    <Button
                        variant="outlined"
                        size="small"
                        aria-expanded={open}
                        aria-controls="table-filters"
                        onClick={() => setOpen((value) => !value)}
                    >
                        {activeFilters > 0
                            ? __('Filters (:count)', { count: activeFilters })
                            : __('Filters')}
                    </Button>
                )}

                {activeFilters > 0 && (
                    <Button variant="outlined" size="small" onClick={clearFilters}>
                        {__('Clear filters')}
                    </Button>
                )}

                <Button variant="outlined" size="small" onClick={onRestore}>
                    {__('Restore defaults')}
                </Button>

                <Stack
                    direction="row"
                    useFlexGap
                    spacing={1}
                    sx={{
                        alignItems: 'center',
                        flexWrap: 'wrap',
                        marginInlineStart: 'auto',
                    }}
                >
                    {children}
                </Stack>
            </Stack>

            {open && (
                <Paper
                    component="form"
                    id="table-filters"
                    variant="outlined"
                    sx={{ p: 2 }}
                    onSubmit={applyFilters}
                >
                    <Box
                        sx={{
                            display: 'grid',
                            gap: 2,
                            gridTemplateColumns: {
                                xs: '1fr',
                                sm: 'repeat(2, 1fr)',
                                lg: 'repeat(3, 1fr)',
                            },
                        }}
                    >
                        {filters.map((filter) => (
                            <ColumnFilter
                                key={filter.id}
                                {...filter}
                                value={draft[filter.id]}
                                onChange={(value) =>
                                    setDraft((current) => ({
                                        ...current,
                                        [filter.id]: value,
                                    }))
                                }
                            />
                        ))}
                    </Box>

                    <Button
                        type="submit"
                        variant="contained"
                        size="small"
                        sx={{ mt: 2 }}
                    >
                        {__('Apply filters')}
                    </Button>
                </Paper>
            )}
        </Stack>
    );
}
