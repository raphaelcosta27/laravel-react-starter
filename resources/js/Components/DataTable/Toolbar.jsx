import ColumnFilter from '@/Components/DataTable/ColumnFilter';
import { useTranslations } from '@/lib/i18n';
import { useState } from 'react';

const buttonClass =
    'rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2';

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
        <div className="space-y-3 py-3">
            <div className="flex flex-wrap items-center gap-2">
                <form
                    role="search"
                    className="flex items-center gap-2"
                    onSubmit={(event) => {
                        event.preventDefault();
                        onSearch(term);
                    }}
                >
                    <label htmlFor="table-search" className="sr-only">
                        {__('Search')}
                    </label>
                    <input
                        id="table-search"
                        type="search"
                        className="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        value={term}
                        onChange={(event) => setTerm(event.target.value)}
                    />
                    <button type="submit" className={buttonClass}>
                        {__('Search')}
                    </button>
                </form>

                {filters.length > 0 && (
                    <button
                        type="button"
                        className={buttonClass}
                        aria-expanded={open}
                        aria-controls="table-filters"
                        onClick={() => setOpen((value) => !value)}
                    >
                        {activeFilters > 0
                            ? __('Filters (:count)', { count: activeFilters })
                            : __('Filters')}
                    </button>
                )}

                {activeFilters > 0 && (
                    <button
                        type="button"
                        className={buttonClass}
                        onClick={clearFilters}
                    >
                        {__('Clear filters')}
                    </button>
                )}

                <button type="button" className={buttonClass} onClick={onRestore}>
                    {__('Restore defaults')}
                </button>

                <div className="ms-auto flex flex-wrap items-center gap-2">
                    {children}
                </div>
            </div>

            {open && (
                <form
                    id="table-filters"
                    className="rounded-md border border-gray-200 bg-white p-4"
                    onSubmit={applyFilters}
                >
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
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
                    </div>

                    <button type="submit" className={`${buttonClass} mt-4`}>
                        {__('Apply filters')}
                    </button>
                </form>
            )}
        </div>
    );
}
