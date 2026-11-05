import SelectField from '@/Components/SelectField';
import { useTranslations } from '@/lib/i18n';
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

const PAGE_SIZES = [10, 25, 50, 100];

export default function Pagination({ meta, onPageChange, onPerPageChange }) {
    const __ = useTranslations();
    const { currentPage, lastPage, perPage, total } = meta;

    return (
        <Stack
            component="nav"
            aria-label={__('Pagination')}
            direction="row"
            useFlexGap
            spacing={2}
            sx={{
                alignItems: 'center',
                flexWrap: 'wrap',
                justifyContent: 'space-between',
                py: 2,
            }}
        >
            <Stack direction="row" spacing={2} sx={{ alignItems: 'center' }}>
                <Typography variant="body2">
                    {__(':count records', { count: total })}
                </Typography>

                <SelectField
                    id="per-page"
                    label={__('Records per page')}
                    fullWidth={false}
                    sx={{ minWidth: 150 }}
                    value={perPage}
                    onChange={(event) =>
                        onPerPageChange(Number(event.target.value))
                    }
                >
                    {PAGE_SIZES.map((size) => (
                        <option key={size} value={size}>
                            {size}
                        </option>
                    ))}
                </SelectField>
            </Stack>

            <Stack direction="row" spacing={1} sx={{ alignItems: 'center' }}>
                <Button
                    variant="outlined"
                    size="small"
                    disabled={currentPage <= 1}
                    onClick={() => onPageChange(currentPage - 1)}
                >
                    {__('Previous page')}
                </Button>

                <Typography variant="body2">
                    {__('Page :page of :last', {
                        page: currentPage,
                        last: lastPage,
                    })}
                </Typography>

                <Button
                    variant="outlined"
                    size="small"
                    disabled={currentPage >= lastPage}
                    onClick={() => onPageChange(currentPage + 1)}
                >
                    {__('Next page')}
                </Button>
            </Stack>
        </Stack>
    );
}
