import DailyChart from '@/Components/Dashboard/DailyChart';
import { useTranslations } from '@/lib/i18n';

export const layout = { section: 'overview', size: 'half' };

export default function UserGrowth({ data }) {
    const __ = useTranslations();

    return <DailyChart label={__('New users')} type="area" {...data} />;
}
