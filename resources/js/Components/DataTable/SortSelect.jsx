import SelectField from '@/Components/SelectField';
import { useTranslations } from '@/lib/i18n';

/**
 * No celular não há cabeçalho para clicar. A ordem por várias colunas, feita no
 * computador, aparece aqui como a ordem padrão.
 */
export default function SortSelect({ columns, value, onChange }) {
    const __ = useTranslations();

    const options = columns.flatMap((column) => [
        {
            value: column.id,
            label: __(':column, ascending', { column: column.label }),
        },
        {
            value: `-${column.id}`,
            label: __(':column, descending', { column: column.label }),
        },
    ]);

    if (options.length === 0) {
        return null;
    }

    const known = options.some((option) => option.value === value);

    return (
        <SelectField
            id="table-sort"
            label={__('Sort by')}
            value={known ? value : ''}
            onChange={(event) => onChange(event.target.value)}
        >
            <option value="">{__('Default order')}</option>

            {options.map((option) => (
                <option key={option.value} value={option.value}>
                    {option.label}
                </option>
            ))}
        </SelectField>
    );
}
