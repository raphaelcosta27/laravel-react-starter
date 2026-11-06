import { usePage } from '@inertiajs/react';
import Avatar from '@mui/material/Avatar';

export function initials(name) {
    const words = name.trim().split(/\s+/).filter(Boolean);
    const picked = words.length > 1 ? [words[0], words.at(-1)] : words;

    return picked
        .map((word) => Array.from(word)[0])
        .join('')
        .toUpperCase();
}

/**
 * Imagem da pessoa autenticada ou, sem ela, as iniciais. Sem `alt`, é decorativa:
 * o nome já aparece em texto ao lado.
 */
export default function UserAvatar({ size = 32, alt = '' }) {
    const { user, avatarUrl } = usePage().props.auth;

    return (
        <Avatar
            src={avatarUrl ?? undefined}
            alt={alt}
            aria-hidden={alt === '' ? true : undefined}
            sx={{
                width: size,
                height: size,
                fontSize: size * 0.42,
                bgcolor: 'primary.main',
                color: 'primary.contrastText',
            }}
        >
            {initials(user.name)}
        </Avatar>
    );
}
