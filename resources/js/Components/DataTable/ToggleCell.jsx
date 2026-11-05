export default function ToggleCell({ checked, label, disabled = false, onChange }) {
    return (
        <button
            type="button"
            role="switch"
            aria-checked={checked}
            aria-label={label}
            disabled={disabled}
            className={`relative inline-flex h-6 w-11 items-center rounded-full border-2 border-gray-600 transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 ${checked ? 'bg-indigo-700' : 'bg-white'}`}
            onClick={() => onChange(!checked)}
        >
            <span
                aria-hidden="true"
                className={`inline-block h-4 w-4 transform rounded-full transition ${checked ? 'translate-x-5 bg-white' : 'translate-x-0.5 bg-gray-600'}`}
            />
        </button>
    );
}
