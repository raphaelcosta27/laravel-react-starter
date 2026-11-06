import ActionIconButton from '@/Components/ActionIconButton';
import ColumnFilter from '@/Components/DataTable/ColumnFilter';
import { useTranslations } from '@/lib/i18n';
import { useShortcuts } from '@/lib/useShortcuts';
import FilterAltIcon from '@mui/icons-material/FilterAlt';
import FilterAltOffIcon from '@mui/icons-material/FilterAltOff';
import RestartAltIcon from '@mui/icons-material/RestartAlt';
import SearchIcon from '@mui/icons-material/Search';
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import InputAdornment from '@mui/material/InputAdornment';
import Paper from '@mui/material/Paper';
import Stack from '@mui/material/Stack';
import TextField from '@mui/material/TextField';
import { useRef, useState } from 'react';

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
    const searchInput = useRef(null);

    useShortcuts({ k: () => searchInput.current?.focus() });

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
                <Box
                    component="form"
                    role="search"
                    sx={{ width: { xs: '100%', sm: 280 } }}
                    onSubmit={(event) => {
                        event.preventDefault();
                        onSearch(term);
                    }}
                >
                    <TextField
                        id="table-search"
                        inputRef={searchInput}
                        type="search"
                        size="small"
                        fullWidth
                        label={__('Search')}
                        value={term}
                        slotProps={{
                            input: {
                                endAdornment: (
                                    <InputAdornment position="end">
                                        <ActionIconButton
                                            type="submit"
                                            icon={SearchIcon}
                                            label={__('Search')}
                                            description={__('Searches the records of this table.')}
                                        />
                                    </InputAdornment>
                                ),
                            },
                        }}
                        onChange={(event) => setTerm(event.target.value)}
                    />
                </Box>

                {filters.length > 0 && (
                    <ActionIconButton
                        icon={FilterAltIcon}
                        label={
                            activeFilters > 0
                                ? __('Filters (:count)', { count: activeFilters })
                                : __('Filters')
                        }
                        description={__('Shows or hides the filters of this table.')}
                        badge={activeFilters}
                        aria-expanded={open}
                        aria-controls="table-filters"
                        onClick={() => setOpen((value) => !value)}
                    />
                )}

                {activeFilters > 0 && (
                    <ActionIconButton
                        icon={FilterAltOffIcon}
                        label={__('Clear filters')}
                        description={__('Removes all the filters in use.')}
                        onClick={clearFilters}
                    />
                )}

                <ActionIconButton
                    icon={RestartAltIcon}
                    label={__('Restore defaults')}
                    description={__('Returns search, filters, order and columns to the initial state.')}
                    onClick={onRestore}
                />

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
                        startIcon={<FilterAltIcon />}
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
