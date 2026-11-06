import BulkActions from '@/Components/DataTable/BulkActions';
import ColumnManager from '@/Components/DataTable/ColumnManager';
import ExportMenu from '@/Components/DataTable/ExportMenu';
import Pagination from '@/Components/DataTable/Pagination';
import RowActions from '@/Components/DataTable/RowActions';
import RowCards from '@/Components/DataTable/RowCards';
import SortSelect from '@/Components/DataTable/SortSelect';
import Tabs from '@/Components/DataTable/Tabs';
import ToggleCell from '@/Components/DataTable/ToggleCell';
import Toolbar from '@/Components/DataTable/Toolbar';
import useTablePreferences from '@/Components/DataTable/useTablePreferences';
import { useTranslations } from '@/lib/i18n';
import { visuallyHidden } from '@/lib/visuallyHidden';
import { router } from '@inertiajs/react';
import ChevronRightIcon from '@mui/icons-material/ChevronRight';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import Box from '@mui/material/Box';
import Checkbox from '@mui/material/Checkbox';
import FormControlLabel from '@mui/material/FormControlLabel';
import IconButton from '@mui/material/IconButton';
import Paper from '@mui/material/Paper';
import { useTheme } from '@mui/material/styles';
import Table from '@mui/material/Table';
import TableBody from '@mui/material/TableBody';
import TableCell from '@mui/material/TableCell';
import TableContainer from '@mui/material/TableContainer';
import TableFooter from '@mui/material/TableFooter';
import TableHead from '@mui/material/TableHead';
import TableRow from '@mui/material/TableRow';
import TableSortLabel from '@mui/material/TableSortLabel';
import useMediaQuery from '@mui/material/useMediaQuery';
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

const WRAP = {
    wrap: { whiteSpace: 'normal' },
    nowrap: { whiteSpace: 'nowrap' },
    truncate: {
        overflow: 'hidden',
        textOverflow: 'ellipsis',
        whiteSpace: 'nowrap',
    },
};

const INTERNAL_COLUMNS = ['select', 'expand', 'actions'];

const CONTROL_COLUMNS = ['select', 'expand'];

const CONTROL_COLUMN_WIDTH = 56;

const CONTROL_CELL = { px: 0, textAlign: 'center' };

const DEFAULT_CARD_FIELDS = 3;

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

function cardFields(columns) {
    const essential = columns.filter((column) => column.meta?.essential);
    const fields =
        essential.length > 0
            ? essential
            : columns
                  .filter((column) => !column.meta?.defaultHidden)
                  .slice(0, DEFAULT_CARD_FIELDS);

    return fields.map((column) => ({
        id: columnId(column),
        label: column.header,
    }));
}

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

const controlSx = (column) =>
    CONTROL_COLUMNS.includes(column.id) ? CONTROL_CELL : {};

const cellSx = (column) => {
    const { meta } = column.columnDef;

    return {
        textAlign: meta?.align ?? 'start',
        ...WRAP[meta?.wrap ?? 'wrap'],
        ...(column.getIsPinned() ? { bgcolor: 'background.paper' } : {}),
        ...controlSx(column),
    };
};

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
    rowSx,
    toolbar,
    emptyMessage,
    exportRoute,
    canExport = false,
}) {
    const __ = useTranslations();
    const theme = useTheme();
    const phone = useMediaQuery(theme.breakpoints.down('sm'), { noSsr: true });
    const [busy, setBusy] = useState(false);

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
    const hiddenByDefault = useMemo(
        () =>
            Object.fromEntries(
                columns
                    .filter((column) => column.meta?.defaultHidden)
                    .map((column) => [columnId(column), false]),
            ),
        [columns],
    );
    const [columnVisibility, setColumnVisibility] = useState({
        ...hiddenByDefault,
        ...preferences.columnVisibility,
    });
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

    // As colunas de controle mantêm a identidade quando os dados recarregam.
    // Uma célula recriada perderia o foco do botão que abriu um formulário.
    const leadingColumns = useMemo(() => {
        const leading = [];

        if (selectable) {
            leading.push({
                id: 'select',
                size: CONTROL_COLUMN_WIDTH,
                header: ({ table }) => (
                    <Checkbox
                        size="small"
                        checked={table.getIsAllPageRowsSelected()}
                        slotProps={{ input: { 'aria-label': __('Select all') } }}
                        onChange={(event) =>
                            table.toggleAllPageRowsSelected(event.target.checked)
                        }
                    />
                ),
                cell: ({ row }) => (
                    <Checkbox
                        size="small"
                        checked={row.getIsSelected()}
                        slotProps={{
                            input: {
                                'aria-label': __('Select :name', {
                                    name: rowLabel(row.original),
                                }),
                            },
                        }}
                        onChange={(event) => row.toggleSelected(event.target.checked)}
                    />
                ),
            });
        }

        if (expandable) {
            leading.push({
                id: 'expand',
                size: CONTROL_COLUMN_WIDTH,
                header: () => (
                    <span style={visuallyHidden}>{__('Details')}</span>
                ),
                cell: ({ row }) => (
                    <IconButton
                        size="small"
                        aria-expanded={row.getIsExpanded()}
                        aria-label={__('Show details of :name', { name: rowLabel(row.original) })}
                        onClick={() => row.toggleExpanded()}
                    >
                        {row.getIsExpanded() ? (
                            <ExpandMoreIcon fontSize="small" />
                        ) : (
                            <ChevronRightIcon fontSize="small" />
                        )}
                    </IconButton>
                ),
            });
        }

        return leading;
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [selectable, expandable]);

    const trailingColumns = useMemo(
        () =>
            rowActions
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
                : [],
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [rowActions],
    );

    const allColumns = useMemo(
        () => [...leadingColumns, ...columns, ...trailingColumns],
        [leadingColumns, columns, trailingColumns],
    );

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
        setColumnVisibility(hiddenByDefault);
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
                {!phone && (
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
                )}

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


            {phone ? (
                <>
                    <Box sx={{ alignItems: 'center', display: 'flex', gap: 1, pb: 1.5 }}>
                        {selectable && tableRows.length > 0 && (
                            <FormControlLabel
                                label={__('Select all')}
                                sx={{ flexShrink: 0, m: 0 }}
                                slotProps={{ typography: { variant: 'body2' } }}
                                control={
                                    <Checkbox
                                        checked={table.getIsAllPageRowsSelected()}
                                        onChange={(event) =>
                                            table.toggleAllPageRowsSelected(event.target.checked)
                                        }
                                    />
                                }
                            />
                        )}

                        <SortSelect
                            columns={columns
                                .filter((column) => column.meta?.sortable)
                                .map((column) => ({
                                    id: columnId(column),
                                    label: column.header,
                                }))}
                            value={query.sort ?? ''}
                            onChange={(sort) => visit({ sort })}
                        />
                    </Box>

                    <RowCards
                        rows={tableRows}
                        label={caption}
                        busy={busy}
                        fields={cardFields(columns)}
                        emptyMessage={emptyMessage}
                        renderCell={renderCell}
                        renderDetail={renderDetail}
                        rowSx={rowSx}
                    />
                </>
            ) : (
                <TableContainer
                    component={Paper}
                    variant="outlined"
                    role="region"
                    aria-label={caption}
                    tabIndex={0}
                    sx={{
                        '&:focus-visible': {
                            outline: '2px solid',
                            outlineColor: 'primary.main',
                        },
                    }}
                >
                    <Table size="small" aria-busy={busy}>
                        <caption style={visuallyHidden}>{caption}</caption>

                        <TableHead>
                            {table.getHeaderGroups().map((group) => (
                                <TableRow key={group.id}>
                                    {group.headers.map((header) => {
                                        const { column } = header;
                                        const { meta } = column.columnDef;
                                        const direction = sortDirection(column.id);

                                        return (
                                            <TableCell
                                                key={header.id}
                                                scope="col"
                                                aria-sort={meta?.sortable ? direction : undefined}
                                                style={columnStyle(column, columnSizing)}
                                                sx={{
                                                    bgcolor: 'grey.100',
                                                    fontWeight: 600,
                                                    position: 'relative',
                                                    textAlign: meta?.align ?? 'start',
                                                    ...controlSx(column),
                                                }}
                                            >
                                                {meta?.sortable ? (
                                                    <TableSortLabel
                                                        component="button"
                                                        active={direction !== 'none'}
                                                        direction={direction === 'descending' ? 'desc' : 'asc'}
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
                                                    </TableSortLabel>
                                                ) : (
                                                    <table.FlexRender header={header} />
                                                )}

                                                {!INTERNAL_COLUMNS.includes(column.id) && (
                                                    <Box
                                                        data-resize-handle
                                                        aria-hidden="true"
                                                        sx={{
                                                            cursor: 'col-resize',
                                                            height: '100%',
                                                            insetInlineEnd: 0,
                                                            position: 'absolute',
                                                            top: 0,
                                                            touchAction: 'none',
                                                            userSelect: 'none',
                                                            width: 4,
                                                            '&:hover': { bgcolor: 'primary.light' },
                                                        }}
                                                        onMouseDown={header.getResizeHandler()}
                                                        onTouchStart={header.getResizeHandler()}
                                                    />
                                                )}
                                            </TableCell>
                                        );
                                    })}
                                </TableRow>
                            ))}
                        </TableHead>

                        <TableBody>
                            {tableRows.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={visibleColumns.length}
                                        sx={{ color: 'text.secondary', py: 4, textAlign: 'center' }}
                                    >
                                        {emptyMessage}
                                    </TableCell>
                                </TableRow>
                            )}

                            {tableRows.map((row) => (
                                <Fragment key={row.id}>
                                    <TableRow sx={rowSx?.(row.original)}>
                                        {row.getVisibleCells().map((cell) => (
                                            <TableCell
                                                key={cell.id}
                                                style={columnStyle(cell.column, columnSizing)}
                                                sx={cellSx(cell.column)}
                                            >
                                                {renderCell(cell)}
                                            </TableCell>
                                        ))}
                                    </TableRow>

                                    {expandable && row.getIsExpanded() && (
                                        <TableRow>
                                            <TableCell
                                                colSpan={visibleColumns.length}
                                                sx={{ bgcolor: 'grey.50' }}
                                            >
                                                {renderDetail(row.original)}
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </Fragment>
                            ))}
                        </TableBody>

                        {hasSummaries && (
                            <TableFooter>
                                <TableRow>
                                    {visibleColumns.map((column, index) => {
                                        const { meta } = column.columnDef;
                                        const value = summaries[column.id]?.[meta?.summary ?? 'sum'];

                                        return (
                                            <TableCell
                                                key={column.id}
                                                sx={{
                                                    bgcolor: 'grey.100',
                                                    color: 'text.primary',
                                                    fontWeight: 600,
                                                    textAlign: meta?.align ?? 'start',
                                                }}
                                            >
                                                {value ?? (index === 0 ? __('Total') : '')}
                                            </TableCell>
                                        );
                                    })}
                                </TableRow>
                            </TableFooter>
                        )}
                    </Table>
                </TableContainer>
            )}

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
