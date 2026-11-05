import { useTranslations } from '@/lib/i18n';
import { router } from '@inertiajs/react';
import Box from '@mui/material/Box';
import TextField from '@mui/material/TextField';
import Typography from '@mui/material/Typography';
import { useEffect, useId, useRef, useState } from 'react';

export default function EditableCell({ value, field, url, label, announce }) {
    const __ = useTranslations();
    const errorId = useId();

    const initial = value ?? '';
    const [draft, setDraft] = useState(initial);
    const [status, setStatus] = useState('idle');
    const [error, setError] = useState(null);

    const saved = useRef(initial);
    const sent = useRef(initial);

    useEffect(() => {
        if (draft === saved.current) {
            setDraft(initial);
            saved.current = initial;
            sent.current = initial;
        }
        // Somente quando o servidor devolve um novo valor.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [initial]);

    const fail = (message) => {
        setStatus('error');
        setError(message);
        announce?.(message);
    };

    const save = () => {
        if (draft === saved.current || draft === sent.current) {
            return;
        }

        const submitted = draft;
        let settled = false;

        sent.current = submitted;
        setStatus('saving');
        setError(null);
        announce?.(__('Saving...'));

        router.patch(
            url,
            { [field]: submitted },
            {
                async: true,
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    settled = true;
                    saved.current = submitted;
                    setStatus('saved');
                    announce?.(__('Saved'));
                },
                onError: (errors) => {
                    settled = true;
                    fail(errors[field] ?? __('Could not save. Try again.'));
                },
                onFinish: () => {
                    if (!settled) {
                        fail(__('Could not save. Try again.'));
                    }
                },
            },
        );
    };

    const cancel = () => {
        setDraft(saved.current);
        sent.current = saved.current;
        setStatus('idle');
        setError(null);
    };

    const onKeyDown = (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            save();
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            cancel();
        }
    };

    return (
        <Box>
            <TextField
                size="small"
                fullWidth
                error={status === 'error'}
                sx={{ minWidth: 128 }}
                slotProps={{
                    htmlInput: {
                        'aria-label': label,
                        'aria-describedby': error ? errorId : undefined,
                    },
                }}
                value={draft}
                onChange={(event) => setDraft(event.target.value)}
                onBlur={save}
                onKeyDown={onKeyDown}
            />

            {status === 'saving' && (
                <Typography variant="caption" display="block">
                    {__('Saving...')}
                </Typography>
            )}

            {status === 'saved' && (
                <Typography variant="caption" display="block" color="success.dark">
                    {__('Saved')}
                </Typography>
            )}

            {error && (
                <Typography
                    id={errorId}
                    variant="caption"
                    display="block"
                    color="error"
                >
                    {error}
                </Typography>
            )}
        </Box>
    );
}
