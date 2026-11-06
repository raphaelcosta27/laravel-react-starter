import DailyChart from '@/Components/Dashboard/DailyChart';
import { useTranslations } from '@/lib/i18n';

export const layout = { section: 'overview', size: 'half' };

export default function SystemMovement({ data }) {
    const __ = useTranslations();

    return <DailyChart label={__('System changes')} type="bar" {...data} />;
}
