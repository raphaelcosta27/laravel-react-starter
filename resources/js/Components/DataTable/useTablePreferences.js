import { usePage } from '@inertiajs/react';
import { useRef, useState } from 'react';

const FIELDS = [
    'perPage',
    'page',
    'sort',
    'search',
    'filter',
    'tab',
    'columnOrder',
    'columnVisibility',
    'columnSizing',
];

const pick = (source, keys) =>
    Object.fromEntries(
        Object.entries(source ?? {}).filter(([key]) => keys.includes(key)),
    );

function read(key) {
    try {
        const stored = JSON.parse(localStorage.getItem(key));

        return stored && typeof stored === 'object' ? stored : {};
    } catch {
        return {};
    }
}

function write(key, value) {
    try {
        localStorage.setItem(key, JSON.stringify(value));
    } catch {
        // Armazenamento bloqueado: a listagem segue sem guardar as escolhas.
    }
}

function remove(key) {
    try {
        localStorage.removeItem(key);
    } catch {
        // Armazenamento bloqueado: não há nada guardado.
    }
}

/**
 * Coluna ou filtro que não existe mais é ignorado, e o restante é aplicado.
 */
function sanitize(stored, columns, filters) {
    const clean = pick(stored, FIELDS);

    if (clean.columnOrder) {
        clean.columnOrder = clean.columnOrder.filter((id) =>
            columns.includes(id),
        );
    }

    ['columnVisibility', 'columnSizing'].forEach((field) => {
        if (clean[field]) {
            clean[field] = pick(clean[field], columns);
        }
    });

    if (clean.filter) {
        clean.filter = pick(clean.filter, filters);
    }

    return clean;
}

export default function useTablePreferences(
    tableKey,
    { columns = [], filters = [] } = {},
) {
    const { auth } = usePage().props;
    const key = `datatable:${auth?.user?.id}:${tableKey}:v1`;

    const [preferences, setPreferences] = useState(() =>
        sanitize(read(key), columns, filters),
    );
    const current = useRef(preferences);

    const update = (next) => {
        current.current = next;
        setPreferences(next);
    };

    return {
        preferences,
        save(changes) {
            const next = { ...current.current, ...pick(changes, FIELDS) };

            write(key, next);
            update(next);
        },
        clear() {
            remove(key);
            update({});
        },
    };
}
