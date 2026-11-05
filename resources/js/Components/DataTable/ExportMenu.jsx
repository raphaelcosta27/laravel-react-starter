import { useTranslations } from '@/lib/i18n';
import Button from '@mui/material/Button';
import Menu from '@mui/material/Menu';
import MenuItem from '@mui/material/MenuItem';
import { useId, useState } from 'react';

function appendParam(search, key, value) {
    if (Array.isArray(value)) {
        value.forEach((item) => appendParam(search, `${key}[]`, item));
    } else if (value !== null && typeof value === 'object') {
        Object.entries(value).forEach(([name, item]) =>
            appendParam(search, `${key}[${name}]`, item),
        );
    } else if (value !== undefined && value !== null && value !== '') {
        search.append(key, value);
    }
}

function buildUrl(base, params) {
    const search = new URLSearchParams();

    Object.entries(params).forEach(([key, value]) =>
        appendParam(search, key, value),
    );

    return `${base}?${search.toString()}`;
}

export default function ExportMenu({ exportRoute, columns, query, selectedIds }) {
    const __ = useTranslations();
    const buttonId = useId();
    const [anchor, setAnchor] = useState(null);

    const url = (format, ids) =>
        buildUrl(route(exportRoute), {
            format,
            columns,
            sort: query.sort,
            search: query.search,
            filter: query.filter,
            tab: query.tab,
            ids,
        });

    const items = [
        { label: 'CSV', href: url('csv') },
        { label: 'XLSX', href: url('xlsx') },
        ...(selectedIds.length > 0
            ? [
                  { label: __('CSV of the selected'), href: url('csv', selectedIds) },
                  { label: __('XLSX of the selected'), href: url('xlsx', selectedIds) },
              ]
            : []),
    ];

    return (
        <>
            <Button
                id={buttonId}
                variant="outlined"
                size="small"
                aria-haspopup="menu"
                aria-expanded={anchor !== null}
                onClick={(event) => setAnchor(event.currentTarget)}
            >
                {__('Export')}
            </Button>

            <Menu
                anchorEl={anchor}
                open={anchor !== null}
                slotProps={{ list: { 'aria-labelledby': buttonId } }}
                onClose={() => setAnchor(null)}
            >
                {items.map((item) => (
                    <MenuItem
                        key={item.label}
                        component="a"
                        href={item.href}
                        onClick={() => setAnchor(null)}
                    >
                        {item.label}
                    </MenuItem>
                ))}
            </Menu>
        </>
    );
}
