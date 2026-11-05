import { useTranslations } from '@/lib/i18n';

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

const fieldClass =
    'block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';

const labelClass = 'mb-1 block text-sm font-medium text-gray-700';

function Range({ id, label, type, from, to, value, onChange, labels }) {
    const change = (key, text) => onChange({ ...value, [key]: text });

    return (
        <fieldset>
            <legend className={labelClass}>{label}</legend>

            <div className="grid grid-cols-2 gap-2">
                {[
                    [from, labels[0]],
                    [to, labels[1]],
                ].map(([key, text]) => (
                    <div key={key}>
                        <label
                            htmlFor={`${id}-${key}`}
                            className="mb-1 block text-xs text-gray-600"
                        >
                            {text}
                        </label>
                        <input
                            id={`${id}-${key}`}
                            type={type}
                            className={fieldClass}
                            value={value?.[key] ?? ''}
                            onChange={(event) =>
                                change(key, event.target.value)
                            }
                        />
                    </div>
                ))}
            </div>
        </fieldset>
    );
}

function TextFilter({ id, label, value, onChange }) {
    const __ = useTranslations();
    const op = value?.op ?? 'contains';

    return (
        <div>
            <label htmlFor={`${id}-op`} className="mb-1 block text-xs text-gray-600">
                {__('Operator of :column', { column: label })}
            </label>
            <select
                id={`${id}-op`}
                className={`${fieldClass} mb-2`}
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
            </select>

            {!WITHOUT_VALUE.includes(op) && (
                <>
                    <label htmlFor={id} className={labelClass}>
                        {label}
                    </label>
                    <input
                        id={id}
                        type="text"
                        className={fieldClass}
                        value={value?.value ?? ''}
                        onChange={(event) =>
                            onChange({ op, value: event.target.value })
                        }
                    />
                </>
            )}
        </div>
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
            <div>
                <label htmlFor={fieldId} className={labelClass}>
                    {label}
                </label>
                <select
                    id={fieldId}
                    multiple
                    className={fieldClass}
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
                </select>
            </div>
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
        <div>
            <label htmlFor={fieldId} className={labelClass}>
                {label}
            </label>
            <select
                id={fieldId}
                className={fieldClass}
                value={value ?? ''}
                onChange={(event) => onChange(event.target.value)}
            >
                <option value="">{__('All')}</option>
                {choices.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </div>
    );
}
