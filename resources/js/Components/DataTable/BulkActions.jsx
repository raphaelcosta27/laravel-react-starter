import ConfirmDialog from '@/Components/ConfirmDialog';
import { useTranslations } from '@/lib/i18n';
import { useState } from 'react';

const buttonClass =
    'rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2';

export default function BulkActions({ actions, rows }) {
    const __ = useTranslations();
    const [pending, setPending] = useState(null);

    if (rows.length === 0) {
        return null;
    }

    const run = (action) => {
        if (action.confirm) {
            setPending(action);

            return;
        }

        action.onClick(rows);
    };

    const confirm = () => {
        pending.onClick(rows);
        setPending(null);
    };

    return (
        <div className="flex flex-wrap items-center gap-2 py-2">
            <span className="text-sm text-gray-700">
                {__(':count selected', { count: rows.length })}
            </span>

            {actions.map((action) => (
                <button
                    key={action.key}
                    type="button"
                    className={`${buttonClass} ${action.variant === 'danger' ? 'text-red-700' : ''}`}
                    onClick={() => run(action)}
                >
                    {action.label}
                </button>
            ))}

            <ConfirmDialog
                show={pending !== null}
                title={pending?.confirm?.title ?? ''}
                message={pending?.confirm?.message}
                danger={pending?.variant === 'danger'}
                onConfirm={confirm}
                onClose={() => setPending(null)}
            />
        </div>
    );
}
