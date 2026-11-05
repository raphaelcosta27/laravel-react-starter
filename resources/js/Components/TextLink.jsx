import { Link } from '@inertiajs/react';
import MuiLink from '@mui/material/Link';

export default function TextLink(props) {
    return <MuiLink component={Link} variant="body2" {...props} />;
}
