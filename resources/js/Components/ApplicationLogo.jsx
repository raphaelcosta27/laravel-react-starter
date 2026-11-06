import { usePage } from '@inertiajs/react';
import { useColorScheme } from '@mui/material/styles';

export default function ApplicationLogo(props) {
    const { appName, branding } = usePage().props;
    const { mode, systemMode } = useColorScheme();
    const dark = (systemMode ?? mode) === 'dark';

    return (
        <img
            src={dark && branding.logoDark ? branding.logoDark : branding.logo}
            alt={appName}
            {...props}
        />
    );
}
