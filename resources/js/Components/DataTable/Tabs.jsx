import { useRef } from 'react';

export default function Tabs({ tabs, current, label, onChange }) {
    const buttons = useRef({});

    const select = (index) => {
        const tab = tabs[(index + tabs.length) % tabs.length];

        buttons.current[tab.key]?.focus();
        onChange(tab.key);
    };

    const onKeyDown = (event, index) => {
        if (event.key === 'ArrowRight') {
            event.preventDefault();
            select(index + 1);
        }

        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            select(index - 1);
        }
    };

    return (
        <div
            role="tablist"
            aria-label={label}
            className="flex flex-wrap gap-1 border-b border-gray-200"
        >
            {tabs.map((tab, index) => {
                const selected = tab.key === current;

                return (
                    <button
                        key={tab.key}
                        ref={(element) => {
                            buttons.current[tab.key] = element;
                        }}
                        type="button"
                        role="tab"
                        aria-selected={selected}
                        tabIndex={selected ? 0 : -1}
                        className={`-mb-px border-b-2 px-3 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500 ${selected ? 'border-indigo-700 text-indigo-800' : 'border-transparent text-gray-700 hover:text-gray-900'}`}
                        onClick={() => onChange(tab.key)}
                        onKeyDown={(event) => onKeyDown(event, index)}
                    >
                        {`${tab.label} (${tab.count})`}
                    </button>
                );
            })}
        </div>
    );
}
