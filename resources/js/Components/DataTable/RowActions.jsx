import ConfirmDialog from '@/Components/ConfirmDialog';
import { useTranslations } from '@/lib/i18n';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/react';
import { Link } from '@inertiajs/react';
import { useState } from 'react';

const baseClass =
    'rounded px-2 py-1 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50';

const variants = {
    default: 'text-indigo-700 hover:text-indigo-900',
    danger: 'text-red-700 hover:text-red-900',
};

const menuItemClass =
    'block w-full px-4 py-2 text-start text-sm data-[focus]:bg-gray-100 data-[disabled]:opacity-50';

const resolve = (value, row) =>
    typeof value === 'function' ? value(row) : value;

export default function RowActions({ row, actions = [], label }) {
    const __ = useTranslations();
    const [pending, setPending] = useState(null);

    const visible = actions.filter((action) => action.visible?.(row) ?? true);

    if (visible.length === 0) {
        return null;
    }

    const run = (action) => {
        if (action.confirm) {
            setPending(action);

            return;
        }

        action.onClick?.(row);
    };

    const confirm = () => {
        pending.onClick?.(row);
        setPending(null);
    };

    const className = (action) =>
        `${baseClass} ${variants[action.variant ?? 'default']}`;

    const inline = visible.filter((action) => !action.menu);
    const grouped = visible.filter((action) => action.menu);
    const details = pending ? resolve(pending.confirm, row) : null;

    return (
        <div role="group" aria-label={label} className="flex items-center gap-1">
            {inline.map((action) =>
                action.href ? (
                    <Link
                        key={action.key}
                        href={action.href(row)}
                        className={className(action)}
                    >
                        {action.label}
                    </Link>
                ) : (
                    <button
                        key={action.key}
                        type="button"
                        className={className(action)}
                        disabled={action.disabled?.(row) ?? false}
                        onClick={() => run(action)}
                    >
                        {action.label}
                    </button>
                ),
            )}

            {grouped.length > 0 && (
                <Menu>
                    <MenuButton className={`${baseClass} ${variants.default}`}>
                        {__('More actions')}
                    </MenuButton>

                    <MenuItems
                        anchor="bottom end"
                        className="z-20 min-w-48 rounded-md border border-gray-200 bg-white py-1 shadow-lg focus:outline-none"
                    >
                        {grouped.map((action) => (
                            <MenuItem
                                key={action.key}
                                disabled={action.disabled?.(row) ?? false}
                            >
                                {action.href ? (
                                    <Link
                                        href={action.href(row)}
                                        className={menuItemClass}
                                    >
                                        {action.label}
                                    </Link>
                                ) : (
                                    <button
                                        type="button"
                                        className={`${menuItemClass} ${variants[action.variant ?? 'default']}`}
                                        onClick={() => run(action)}
                                    >
                                        {action.label}
                                    </button>
                                )}
                            </MenuItem>
                        ))}
                    </MenuItems>
                </Menu>
            )}

            <ConfirmDialog
                show={pending !== null}
                title={details?.title ?? ''}
                message={details?.message}
                danger={pending?.variant === 'danger'}
                onConfirm={confirm}
                onClose={() => setPending(null)}
            />
        </div>
    );
}
