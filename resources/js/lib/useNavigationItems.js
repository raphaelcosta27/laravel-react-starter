import { useTranslations } from '@/lib/i18n';
import { useCan } from '@/lib/permissions';
import { navigation } from '@/navigation';

/** Módulos que a pessoa pode abrir, prontos para qualquer menu. */
export function useNavigationItems() {
    const __ = useTranslations();
    const can = useCan();

    return navigation
        .filter((item) => item.permission === null || can(item.permission))
        .map((item) => ({
            ...item,
            label: __(item.label),
            href: route(item.index),
            active: Boolean(
                route().current(item.route) ||
                    route().current(`${item.route}.*`),
            ),
        }));
}
