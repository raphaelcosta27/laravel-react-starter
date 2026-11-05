import { usePage } from '@inertiajs/react';

export default function ApplicationLogo(props) {
    const { appName, branding } = usePage().props;

    return <img src={branding.logo} alt={appName} {...props} />;
}
