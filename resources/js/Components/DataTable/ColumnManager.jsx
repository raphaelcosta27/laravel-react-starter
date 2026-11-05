import { useTranslations } from '@/lib/i18n';
import { useState } from 'react';

const DEFAULT_WIDTH = 150;
const MIN_WIDTH = 60;
const STEP = 20;

const triggerClass =
    'rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2';

const stepClass =
    'min-h-6 min-w-6 rounded border border-gray-300 px-2 text-sm text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50';

/**
 * Botões como alternativa ao arraste, que funcionam pelo teclado (WCAG 2.5.7).
 */
export default function ColumnManager({
    columns,
    order,
    visibility,
    sizing,
    onVisibilityChange,
    onOrderChange,
    onSizeChange,
}) {
    const __ = useTranslations();
    const [open, setOpen] = useState(false);

    const position = (id) => order.indexOf(id);

    const ordered = [...columns].sort((a, b) => position(a.id) - position(b.id));

    const move = (id, offset) => {
        const next = [...order];
        const from = next.indexOf(id);

        next.splice(from, 1);
        next.splice(from + offset, 0, id);

        onOrderChange(next);
    };

    const resize = (id, change) =>
        onSizeChange({
            ...sizing,
            [id]: Math.max(MIN_WIDTH, (sizing[id] ?? DEFAULT_WIDTH) + change),
        });

    return (
        <div className="relative">
            <button
                type="button"
                className={triggerClass}
                aria-expanded={open}
                onClick={() => setOpen((value) => !value)}
            >
                {__('Columns')}
            </button>

            {open && (
                <ul className="absolute end-0 z-20 mt-2 w-80 max-w-[calc(100vw-2rem)] space-y-2 rounded-md border border-gray-200 bg-white p-3 shadow-lg">
                    {ordered.map((column) => (
                        <li
                            key={column.id}
                            className="flex items-center justify-between gap-2"
                        >
                            <label className="flex items-center gap-2 text-sm text-gray-800">
                                <input
                                    type="checkbox"
                                    className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    checked={visibility[column.id] !== false}
                                    disabled={column.required}
                                    onChange={(event) =>
                                        onVisibilityChange({
                                            ...visibility,
                                            [column.id]: event.target.checked,
                                        })
                                    }
                                />
                                {column.label}
                            </label>

                            <span className="flex gap-1">
                                <button
                                    type="button"
                                    className={stepClass}
                                    aria-label={__('Move :column left', { column: column.label })}
                                    disabled={position(column.id) <= 0}
                                    onClick={() => move(column.id, -1)}
                                >
                                    ←
                                </button>
                                <button
                                    type="button"
                                    className={stepClass}
                                    aria-label={__('Move :column right', { column: column.label })}
                                    disabled={position(column.id) >= order.length - 1}
                                    onClick={() => move(column.id, 1)}
                                >
                                    →
                                </button>
                                <button
                                    type="button"
                                    className={stepClass}
                                    aria-label={__('Decrease width of :column', { column: column.label })}
                                    onClick={() => resize(column.id, -STEP)}
                                >
                                    −
                                </button>
                                <button
                                    type="button"
                                    className={stepClass}
                                    aria-label={__('Increase width of :column', { column: column.label })}
                                    onClick={() => resize(column.id, STEP)}
                                >
                                    +
                                </button>
                            </span>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
