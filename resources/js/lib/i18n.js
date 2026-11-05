import { usePage } from '@inertiajs/react';

export function useTranslations() {
    const { translations = {} } = usePage().props;

    return (key, params = {}) =>
        Object.entries(params).reduce(
            (text, [name, value]) => text.replaceAll(`:${name}`, value),
            translations[key] ?? key,
        );
}
