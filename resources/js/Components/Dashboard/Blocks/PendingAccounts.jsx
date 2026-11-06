import MetricList from '@/Components/Dashboard/MetricList';

export default function PendingAccounts({ data }) {
    return <MetricList metrics={data.metrics} />;
}
