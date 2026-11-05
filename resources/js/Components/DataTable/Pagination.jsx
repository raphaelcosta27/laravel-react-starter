import { useTranslations } from '@/lib/i18n';

const PAGE_SIZES = [10, 25, 50, 100];

const buttonClass =
    'rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50';

export default function Pagination({ meta, onPageChange, onPerPageChange }) {
    const __ = useTranslations();
    const { currentPage, lastPage, perPage, total } = meta;

    return (
        <nav
            aria-label={__('Pagination')}
            className="flex flex-wrap items-center justify-between gap-3 py-3 text-sm text-gray-700"
        >
            <div className="flex items-center gap-3">
                <span>{__(':count records', { count: total })}</span>

                <label htmlFor="per-page" className="sr-only">
                    {__('Records per page')}
                </label>
                <select
                    id="per-page"
                    className="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                </select>
            </div>

            <div className="flex items-center gap-2">
                <button
                    type="button"
                    className={buttonClass}
                    disabled={currentPage <= 1}
                    onClick={() => onPageChange(currentPage - 1)}
                >
                    {__('Previous page')}
                </button>

                <span>
                    {__('Page :page of :last', {
                        page: currentPage,
                        last: lastPage,
                    })}
                </span>

                <button
                    type="button"
                    className={buttonClass}
                    disabled={currentPage >= lastPage}
                    onClick={() => onPageChange(currentPage + 1)}
                >
                    {__('Next page')}
                </button>
            </div>
        </nav>
    );
}
