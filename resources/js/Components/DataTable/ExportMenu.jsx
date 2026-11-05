import { useTranslations } from '@/lib/i18n';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/react';

const triggerClass =
    'rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2';

const itemClass =
    'block px-4 py-2 text-sm text-gray-800 data-[focus]:bg-gray-100';

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
        <Menu>
            <MenuButton className={triggerClass}>{__('Export')}</MenuButton>

            <MenuItems
                anchor="bottom end"
                className="z-20 min-w-44 rounded-md border border-gray-200 bg-white py-1 shadow-lg focus:outline-none"
            >
                {items.map((item) => (
                    <MenuItem key={item.label}>
                        <a href={item.href} className={itemClass}>
                            {item.label}
                        </a>
                    </MenuItem>
                ))}
            </MenuItems>
        </Menu>
    );
}
