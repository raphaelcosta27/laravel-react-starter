import MetricList from '@/Components/Dashboard/MetricList';
import { useTranslations } from '@/lib/i18n';

export default function Accesses({ data }) {
    const __ = useTranslations();

    return (
        <MetricList
            metrics={[
                {
                    key: 'active',
                    label: __('Active in the last :days days', { days: data.days }),
                    value: data.active,
                },
                {
                    key: 'without_record',
                    label: __('Without recorded access'),
                    value: data.without_record,
                },
            ]}
        />
    );
}
