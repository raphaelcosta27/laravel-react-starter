import MetricList from '@/Components/Dashboard/MetricList';

export default function ProfileDistribution({ data }) {
    return <MetricList metrics={data.metrics} shares />;
}
