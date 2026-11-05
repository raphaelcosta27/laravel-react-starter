import SelectField from '@/Components/SelectField';
import { useTranslations } from '@/lib/i18n';
import Box from '@mui/material/Box';
import FormControl from '@mui/material/FormControl';
import FormLabel from '@mui/material/FormLabel';
import Stack from '@mui/material/Stack';
import TextField from '@mui/material/TextField';

const OPERATORS = {
    contains: 'Contains',
    not_contains: 'Does not contain',
    is: 'Is',
    is_not: 'Is not',
    starts_with: 'Starts with',
    ends_with: 'Ends with',
    is_empty: 'Is empty',
    is_not_empty: 'Is not empty',
};

const WITHOUT_VALUE = ['is_empty', 'is_not_empty'];

function Range({ id, label, type, from, to, value, onChange, labels }) {
    const change = (key, text) => onChange({ ...value, [key]: text });

    return (
        <FormControl component="fieldset" fullWidth>
            <FormLabel component="legend" sx={{ mb: 1, typography: 'body2' }}>
                {label}
            </FormLabel>

            <Box
                sx={{
                    display: 'grid',
                    gap: 1,
                    gridTemplateColumns: 'repeat(2, 1fr)',
                }}
            >
                {[
                    [from, labels[0]],
                    [to, labels[1]],
                ].map(([key, text]) => (
                    <TextField
                        key={key}
                        id={`${id}-${key}`}
                        label={text}
                        type={type}
                        size="small"
                        slotProps={{ inputLabel: { shrink: true } }}
                        value={value?.[key] ?? ''}
                        onChange={(event) => change(key, event.target.value)}
                    />
                ))}
            </Box>
        </FormControl>
    );
}

function TextFilter({ id, label, value, onChange }) {
    const __ = useTranslations();
    const op = value?.op ?? 'contains';

    return (
        <Stack spacing={1.5}>
            <SelectField
                id={`${id}-op`}
                label={__('Operator of :column', { column: label })}
                value={op}
                onChange={(event) =>
                    onChange({ ...value, op: event.target.value })
                }
            >
                {Object.entries(OPERATORS).map(([key, text]) => (
                    <option key={key} value={key}>
                        {__(text)}
                    </option>
                ))}
            </SelectField>

            {!WITHOUT_VALUE.includes(op) && (
                <TextField
                    id={id}
                    label={label}
                    size="small"
                    fullWidth
                    value={value?.value ?? ''}
                    onChange={(event) =>
                        onChange({ op, value: event.target.value })
                    }
                />
            )}
        </Stack>
    );
}

export default function ColumnFilter({ id, label, type, options = [], value, onChange }) {
    const __ = useTranslations();
    const fieldId = `filter-${id}`;

    if (type === 'text') {
        return (
            <TextFilter id={fieldId} label={label} value={value} onChange={onChange} />
        );
    }

    if (type === 'number') {
        return (
            <Range
                id={fieldId}
                label={label}
                type="number"
                from="min"
                to="max"
                labels={[__('Minimum'), __('Maximum')]}
                value={value}
                onChange={onChange}
            />
        );
    }

    if (type === 'date' || type === 'datetime') {
        return (
            <Range
                id={fieldId}
                label={label}
                type={type === 'date' ? 'date' : 'datetime-local'}
                from="from"
                to="to"
                labels={[__('From'), __('To')]}
                value={value}
                onChange={onChange}
            />
        );
    }

    if (type === 'multiselect') {
        return (
            <SelectField
                id={fieldId}
                label={label}
                multiple
                value={value ?? []}
                onChange={(event) =>
                    onChange(
                        [...event.target.selectedOptions].map(
                            (option) => option.value,
                        ),
                    )
                }
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </SelectField>
        );
    }

    const choices =
        type === 'boolean'
            ? [
                  { value: '1', label: __('Yes') },
                  { value: '0', label: __('No') },
              ]
            : options;

    return (
        <SelectField
            id={fieldId}
            label={label}
            value={value ?? ''}
            onChange={(event) => onChange(event.target.value)}
        >
            <option value="">{__('All')}</option>
            {choices.map((option) => (
                <option key={option.value} value={option.value}>
                    {option.label}
                </option>
            ))}
        </SelectField>
    );
}
