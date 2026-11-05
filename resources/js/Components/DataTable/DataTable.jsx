import BulkActions from '@/Components/DataTable/BulkActions';
import ColumnManager from '@/Components/DataTable/ColumnManager';
import EditableCell from '@/Components/DataTable/EditableCell';
import ExportMenu from '@/Components/DataTable/ExportMenu';
import Pagination from '@/Components/DataTable/Pagination';
import RowActions from '@/Components/DataTable/RowActions';
import Tabs from '@/Components/DataTable/Tabs';
import ToggleCell from '@/Components/DataTable/ToggleCell';
import Toolbar from '@/Components/DataTable/Toolbar';
import useTablePreferences from '@/Components/DataTable/useTablePreferences';
import StatusAnnouncer from '@/Components/StatusAnnouncer';
import { useTranslations } from '@/lib/i18n';
import { router } from '@inertiajs/react';
import {
    columnFilteringFeature,
    columnOrderingFeature,
    columnPinningFeature,
    columnResizingFeature,
    columnSizingFeature,
    columnVisibilityFeature,
    rowExpandingFeature,
    rowPaginationFeature,
    rowSelectionFeature,
    rowSortingFeature,
    tableFeatures,
    useTable,
} from '@tanstack/react-table';
import { Fragment, useEffect, useMemo, useState } from 'react';

const features = tableFeatures({
    columnFilteringFeature,
    columnOrderingFeature,
    columnPinningFeature,
    columnResizingFeature,
    columnSizingFeature,
    columnVisibilityFeature,
    rowExpandingFeature,
    rowPaginationFeature,
    rowSelectionFeature,
    rowSortingFeature,
});

const ALIGN = { start: 'text-start', center: 'text-center', end: 'text-end' };

const WRAP = {
    wrap: 'whitespace-normal',
    nowrap: 'whitespace-nowrap',
    truncate: 'truncate',
};

const INTERNAL_COLUMNS = ['select', 'expand', 'actions'];

const CONTROL_COLUMN_WIDTH = 56;

const columnId = (column) => column.id ?? column.accessorKey;

const rowLabel = (row) => row.name ?? row.title ?? row.id;

const parseSort = (sort) =>
    (sort ?? '')
        .split(',')
        .filter(Boolean)
        .map((item) => ({
            id: item.replace(/^-/, ''),
            desc: item.startsWith('-'),
        }));

const serializeSort = (sorting) =>
    sorting.map(({ id, desc }) => `${desc ? '-' : ''}${id}`).join(',');

function nextSort(sorting, id, append) {
    const current = sorting.find((item) => item.id === id);
    const toggled = { id, desc: current ? !current.desc : false };

    if (!append) {
        return [toggled];
    }

    return current
        ? sorting.map((item) => (item.id === id ? toggled : item))
        : [...sorting, toggled];
}

const withoutEmpty = (params) =>
    Object.fromEntries(
        Object.entries(params).filter(
            ([, value]) =>
                value !== undefined &&
                value !== null &&
                value !== '' &&
                !(typeof value === 'object' && Object.keys(value).length === 0),
        ),
    );

const resolveUpdater = (updater, current) =>
    typeof updater === 'function' ? updater(current) : updater;

function mergeOrder(stored, ids) {
    const known = (stored ?? []).filter((id) => ids.includes(id));

    return [...known, ...ids.filter((id) => !known.includes(id))];
}

function columnStyle(column, sizing) {
    const pinned = column.getIsPinned();
    const width = pinned ? column.getSize() : sizing[column.id];

    return {
        ...(width ? { width: `${width}px`, minWidth: `${width}px` } : {}),
        ...(pinned
            ? {
                  position: 'sticky',
                  zIndex: 1,
                  insetInlineStart:
                      pinned === 'start'
                          ? `${column.getStart('start')}px`
                          : undefined,
                  insetInlineEnd:
                      pinned === 'end' ? `${column.getAfter('end')}px` : undefined,
              }
            : {}),
    };
}

const cellClass = (meta, pinned) =>
    `px-4 py-3 text-sm text-gray-800 ${ALIGN[meta?.align ?? 'start']} ${WRAP[meta?.wrap ?? 'wrap']} ${pinned ? 'bg-white' : ''}`;

export default function DataTable({
    tableKey,
    caption,
    columns,
    rows,
    query,
    tabs = [],
    summaries = {},
    rowActions,
    bulkActions = [],
    renderDetail,
    rowClassName,
    toolbar,
    emptyMessage,
    exportRoute,
    canExport = false,
}) {
    const __ = useTranslations();
    const [busy, setBusy] = useState(false);
    const [announcement, setAnnouncement] = useState('');

    const dataColumnIds = useMemo(() => columns.map(columnId), [columns]);
    const sorting = useMemo(() => parseSort(query.sort), [query.sort]);

    const { preferences, save, clear } = useTablePreferences(tableKey, {
        columns: dataColumnIds,
        filters: columns.filter((c) => c.meta?.filter).map(columnId),
    });

    const [storedOrder, setOrder] = useState(preferences.columnOrder);
    const order = useMemo(
        () => mergeOrder(storedOrder, dataColumnIds),
        [storedOrder, dataColumnIds],
    );
    const [columnVisibility, setColumnVisibility] = useState(
        preferences.columnVisibility ?? {},
    );
    const [columnSizing, setColumnSizing] = useState(
        preferences.columnSizing ?? {},
    );
    const [rowSelection, setRowSelection] = useState({});
    const [expanded, setExpanded] = useState({});

    useEffect(() => {
        setRowSelection({});
    }, [rows.data]);

    const selectable = bulkActions.length > 0;
    const expandable = renderDetail !== undefined;

    const allColumns = useMemo(() => {
        const leading = [];

        if (selectable) {
            leading.push({
                id: 'select',
                size: CONTROL_COLUMN_WIDTH,
                header: ({ table }) => (
                    <input
                        type="checkbox"
                        aria-label={__('Select all')}
                        className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        checked={table.getIsAllPageRowsSelected()}
                        onChange={(event) =>
                            table.toggleAllPageRowsSelected(event.target.checked)
                        }
                    />
                ),
                cell: ({ row }) => (
                    <input
                        type="checkbox"
                        aria-label={__('Select :name', { name: rowLabel(row.original) })}
                        className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        checked={row.getIsSelected()}
                        onChange={(event) => row.toggleSelected(event.target.checked)}
                    />
                ),
            });
        }

        if (expandable) {
            leading.push({
                id: 'expand',
                size: CONTROL_COLUMN_WIDTH,
                header: () => <span className="sr-only">{__('Details')}</span>,
                cell: ({ row }) => (
                    <button
                        type="button"
                        aria-expanded={row.getIsExpanded()}
                        aria-label={__('Show details of :name', { name: rowLabel(row.original) })}
                        className="min-h-6 min-w-6 rounded text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        onClick={() => row.toggleExpanded()}
                    >
                        <span aria-hidden="true">
                            {row.getIsExpanded() ? '▾' : '▸'}
                        </span>
                    </button>
                ),
            });
        }

        const trailing = rowActions
            ? [
                  {
                      id: 'actions',
                      header: __('Actions'),
                      cell: ({ row }) => (
                          <RowActions
                              row={row.original}
                              actions={rowActions(row.original)}
                              label={__('Actions')}
                          />
                      ),
                  },
              ]
            : [];

        return [...leading, ...columns, ...trailing];
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [columns, rowActions, selectable, expandable]);

    const columnOrder = useMemo(
        () => [
            ...(selectable ? ['select'] : []),
            ...(expandable ? ['expand'] : []),
            ...order,
            ...(rowActions ? ['actions'] : []),
        ],
        [order, selectable, expandable, rowActions],
    );

    const columnPinning = useMemo(() => {
        const pinned = (position) =>
            columns
                .filter((column) => column.meta?.pinned === position)
                .map(columnId);

        const start = pinned('start');

        // As colunas de controle vêm antes, para a ordem da tela não mudar.
        return {
            start:
                start.length > 0
                    ? [
                          ...(selectable ? ['select'] : []),
                          ...(expandable ? ['expand'] : []),
                          ...start,
                      ]
                    : [],
            end: pinned('end'),
        };
    }, [columns, selectable, expandable]);

    const changeVisibility = (next) => {
        setColumnVisibility(next);
        save({ columnVisibility: next });
    };

    const changeOrder = (next) => {
        setOrder(next);
        save({ columnOrder: next });
    };

    const changeSizing = (updater) => {
        const next = resolveUpdater(updater, columnSizing);

        setColumnSizing(next);
        save({ columnSizing: next });
    };

    const table = useTable({
        features,
        columns: allColumns,
        data: rows.data,
        getRowId: (row) => String(row.id),
        getRowCanExpand: () => expandable,
        enableRowSelection: selectable,
        manualSorting: true,
        manualFiltering: true,
        manualPagination: true,
        manualExpanding: true,
        rowCount: rows.meta.total,
        columnResizeMode: 'onEnd',
        state: {
            sorting,
            pagination: {
                pageIndex: rows.meta.currentPage - 1,
                pageSize: rows.meta.perPage,
            },
            columnOrder,
            columnPinning,
            columnVisibility,
            columnSizing,
            rowSelection,
            expanded,
        },
        onColumnVisibilityChange: (updater) =>
            changeVisibility(resolveUpdater(updater, columnVisibility)),
        onColumnSizingChange: changeSizing,
        onRowSelectionChange: (updater) =>
            setRowSelection((current) => resolveUpdater(updater, current)),
        onExpandedChange: (updater) =>
            setExpanded((current) => resolveUpdater(updater, current)),
    });

    const params = (changes = {}) => ({
        page: rows.meta.currentPage > 1 ? rows.meta.currentPage : undefined,
        per_page: query.per_page,
        sort: query.sort,
        search: query.search,
        filter: query.filter,
        tab: query.tab,
        ...changes,
    });

    const request = (data) =>
        router.get(window.location.pathname, data, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => setBusy(true),
            onFinish: () => setBusy(false),
        });

    const visit = (changes) => {
        const next = params(changes);

        save({
            perPage: next.per_page,
            page: next.page,
            sort: next.sort,
            search: next.search,
            filter: next.filter,
            tab: next.tab,
        });

        request(withoutEmpty(next));
    };

    useEffect(() => {
        if (window.location.search !== '') {
            return;
        }

        const stored = withoutEmpty({
            page: preferences.page,
            per_page: preferences.perPage,
            sort: preferences.sort,
            search: preferences.search,
            filter: preferences.filter,
            tab: preferences.tab,
        });

        if (Object.keys(stored).length > 0) {
            request(stored);
        }
        // Somente ao abrir a listagem.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const restore = () => {
        clear();
        setOrder(undefined);
        setColumnVisibility({});
        setColumnSizing({});
        request({});
    };

    const filters = columns
        .filter((column) => column.meta?.filter)
        .map((column) => {
            const filter = column.meta.filter;

            return {
                id: columnId(column),
                label: column.header,
                type: filter.type ?? filter,
                options: filter.options,
            };
        });

    const sortDirection = (id) => {
        const current = sorting.find((item) => item.id === id);

        if (!current) {
            return 'none';
        }

        return current.desc ? 'descending' : 'ascending';
    };

    const renderCell = (cell) => {
        const { columnDef } = cell.column;
        const { meta } = columnDef;
        const record = cell.row.original;

        if (meta?.editable?.(record)) {
            return (
                <EditableCell
                    value={cell.getValue()}
                    field={columnDef.accessorKey}
                    url={meta.updateRoute(record)}
                    label={__(':column of :name', {
                        column: columnDef.header,
                        name: rowLabel(record),
                    })}
                    announce={setAnnouncement}
                />
            );
        }

        if (meta?.toggle) {
            return (
                <ToggleCell
                    checked={Boolean(cell.getValue())}
                    label={meta.toggle.label(record)}
                    disabled={meta.toggle.disabled?.(record) ?? false}
                    onChange={(value) => meta.toggle.onToggle(record, value)}
                />
            );
        }

        return <table.FlexRender cell={cell} />;
    };

    const tableRows = table.getRowModel().rows;
    const visibleColumns = table.getVisibleLeafColumns();
    const hasSummaries = Object.keys(summaries).length > 0;
    const selectedRows = rows.data.filter((row) => rowSelection[String(row.id)]);

    return (
        <div>
            {tabs.length > 0 && (
                <Tabs
                    tabs={tabs}
                    current={query.tab}
                    label={caption}
                    onChange={(tab) => visit({ tab, page: undefined })}
                />
            )}

            <Toolbar
                filters={filters}
                query={query}
                onSearch={(search) => visit({ search, page: undefined })}
                onFilter={(filter) => visit({ filter, page: undefined })}
                onRestore={restore}
            >
                <ColumnManager
                    columns={columns.map((column) => ({
                        id: columnId(column),
                        label: column.header,
                        required: column.meta?.required ?? false,
                    }))}
                    order={order}
                    visibility={columnVisibility}
                    sizing={columnSizing}
                    onVisibilityChange={changeVisibility}
                    onOrderChange={changeOrder}
                    onSizeChange={changeSizing}
                />

                {canExport && exportRoute && (
                    <ExportMenu
                        exportRoute={exportRoute}
                        columns={visibleColumns
                            .map((column) => column.id)
                            .filter((id) => !INTERNAL_COLUMNS.includes(id))}
                        query={query}
                        selectedIds={selectedRows.map((row) => row.id)}
                    />
                )}

                {toolbar}
            </Toolbar>

            {selectable && <BulkActions actions={bulkActions} rows={selectedRows} />}

            <StatusAnnouncer message={announcement} visuallyHidden />

            <div
                role="region"
                aria-label={caption}
                tabIndex={0}
                className="overflow-x-auto rounded-md border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
                <table
                    className="min-w-full divide-y divide-gray-200"
                    aria-busy={busy}
                >
                    <caption className="sr-only">{caption}</caption>

                    <thead className="bg-gray-50">
                        {table.getHeaderGroups().map((group) => (
                            <tr key={group.id}>
                                {group.headers.map((header) => {
                                    const { column } = header;
                                    const { meta } = column.columnDef;

                                    return (
                                        <th
                                            key={header.id}
                                            scope="col"
                                            aria-sort={
                                                meta?.sortable
                                                    ? sortDirection(column.id)
                                                    : undefined
                                            }
                                            style={columnStyle(column, columnSizing)}
                                            className={`relative bg-gray-50 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-700 ${ALIGN[meta?.align ?? 'start']}`}
                                        >
                                            {meta?.sortable ? (
                                                <button
                                                    type="button"
                                                    className="rounded uppercase focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                                    onClick={(event) =>
                                                        visit({
                                                            sort: serializeSort(
                                                                nextSort(
                                                                    sorting,
                                                                    column.id,
                                                                    event.shiftKey,
                                                                ),
                                                            ),
                                                        })
                                                    }
                                                >
                                                    <table.FlexRender header={header} />
                                                </button>
                                            ) : (
                                                <table.FlexRender header={header} />
                                            )}

                                            {!INTERNAL_COLUMNS.includes(column.id) && (
                                                <div
                                                    data-resize-handle
                                                    aria-hidden="true"
                                                    className="absolute end-0 top-0 h-full w-1 cursor-col-resize touch-none select-none hover:bg-indigo-400"
                                                    onMouseDown={header.getResizeHandler()}
                                                    onTouchStart={header.getResizeHandler()}
                                                />
                                            )}
                                        </th>
                                    );
                                })}
                            </tr>
                        ))}
                    </thead>

                    <tbody className="divide-y divide-gray-200">
                        {tableRows.length === 0 && (
                            <tr>
                                <td
                                    colSpan={visibleColumns.length}
                                    className="px-4 py-8 text-center text-sm text-gray-600"
                                >
                                    {emptyMessage}
                                </td>
                            </tr>
                        )}

                        {tableRows.map((row) => (
                            <Fragment key={row.id}>
                                <tr className={rowClassName?.(row.original)}>
                                    {row.getVisibleCells().map((cell) => (
                                        <td
                                            key={cell.id}
                                            style={columnStyle(cell.column, columnSizing)}
                                            className={cellClass(
                                                cell.column.columnDef.meta,
                                                cell.column.getIsPinned(),
                                            )}
                                        >
                                            {renderCell(cell)}
                                        </td>
                                    ))}
                                </tr>

                                {expandable && row.getIsExpanded() && (
                                    <tr>
                                        <td
                                            colSpan={visibleColumns.length}
                                            className="bg-gray-50 px-4 py-3 text-sm text-gray-800"
                                        >
                                            {renderDetail(row.original)}
                                        </td>
                                    </tr>
                                )}
                            </Fragment>
                        ))}
                    </tbody>

                    {hasSummaries && (
                        <tfoot className="bg-gray-50">
                            <tr>
                                {visibleColumns.map((column, index) => {
                                    const { meta } = column.columnDef;
                                    const value = summaries[column.id]?.[meta?.summary ?? 'sum'];

                                    return (
                                        <td
                                            key={column.id}
                                            className={`px-4 py-3 text-sm font-semibold text-gray-800 ${ALIGN[meta?.align ?? 'start']}`}
                                        >
                                            {value ?? (index === 0 ? __('Total') : '')}
                                        </td>
                                    );
                                })}
                            </tr>
                        </tfoot>
                    )}
                </table>
            </div>

            <Pagination
                meta={rows.meta}
                onPageChange={(page) => visit({ page: page > 1 ? page : undefined })}
                onPerPageChange={(perPage) =>
                    visit({ per_page: perPage, page: undefined })
                }
            />
        </div>
    );
}
