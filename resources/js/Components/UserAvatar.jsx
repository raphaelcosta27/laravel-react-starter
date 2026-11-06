import PersonAvatar, { initials } from '@/Components/PersonAvatar';
import { usePage } from '@inertiajs/react';

export { initials };

/** Foto da pessoa autenticada. */
export default function UserAvatar({ size = 32, alt = '' }) {
    const { user, avatarUrl } = usePage().props.auth;

    return <PersonAvatar name={user.name} src={avatarUrl} size={size} alt={alt} />;
}
