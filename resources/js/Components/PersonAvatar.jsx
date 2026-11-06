import Avatar from '@mui/material/Avatar';
import { useState } from 'react';

export function initials(name) {
    const words = name.trim().split(/\s+/).filter(Boolean);
    const picked = words.length > 1 ? [words[0], words.at(-1)] : words;

    return picked
        .map((word) => Array.from(word)[0])
        .join('')
        .toUpperCase();
}

/**
 * Foto da pessoa ou, sem ela, as iniciais. Sem `alt`, é decorativa: o nome já
 * aparece em texto ao lado. A imagem vai como filha porque a propriedade `src`
 * do Avatar baixa o arquivo na hora, mesmo fora da tela.
 */
export default function PersonAvatar({ name, src, size = 32, alt = '' }) {
    const [failed, setFailed] = useState(null);
    const showPicture = Boolean(src) && failed !== src;

    return (
        <Avatar
            aria-hidden={alt === '' ? true : undefined}
            sx={{
                width: size,
                height: size,
                fontSize: size * 0.42,
                bgcolor: 'primary.main',
                color: 'primary.contrastText',
            }}
        >
            {showPicture ? (
                <img
                    src={src}
                    alt={alt}
                    loading="lazy"
                    width={size}
                    height={size}
                    style={{ height: '100%', objectFit: 'cover', width: '100%' }}
                    onError={() => setFailed(src)}
                />
            ) : (
                initials(name)
            )}
        </Avatar>
    );
}
