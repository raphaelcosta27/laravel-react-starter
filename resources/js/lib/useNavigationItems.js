import { useTranslations } from '@/lib/i18n';
import { useCan } from '@/lib/permissions';
import { CONFIG_GROUP, navigation } from '@/navigation';
import { navigationIcon } from '@/navigationIcons';
import { usePage } from '@inertiajs/react';

/**
 * Itens declarados pelos pacotes (prop `packageNavigation`), no mesmo formato
 * de navigation.js. Vêm depois dos itens do Template.
 */
function packageItems(items) {
    return (items ?? []).map((item) => ({
        ...item,
        icon: navigationIcon(item.icon),
        group: item.group === 'config' ? CONFIG_GROUP : undefined,
    }));
}

/** Módulos que a pessoa pode abrir, prontos para qualquer menu. */
export function useNavigationItems() {
    const __ = useTranslations();
    const can = useCan();
    const { packageNavigation } = usePage().props;

    return [...navigation, ...packageItems(packageNavigation)]
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
