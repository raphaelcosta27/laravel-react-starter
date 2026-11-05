import ConfirmDialog from '@/Components/ConfirmDialog';
import { useTranslations } from '@/lib/i18n';
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import { useState } from 'react';

export default function BulkActions({ actions, rows }) {
    const __ = useTranslations();
    const [pending, setPending] = useState(null);

    if (rows.length === 0) {
        return null;
    }

    const run = (action) => {
        if (action.confirm) {
            setPending(action);

            return;
        }

        action.onClick(rows);
    };

    const confirm = () => {
        pending.onClick(rows);
        setPending(null);
    };

    return (
        <Stack
            direction="row"
            useFlexGap
            spacing={1}
            sx={{ alignItems: 'center', flexWrap: 'wrap', py: 1 }}
        >
            <Typography variant="body2">
                {__(':count selected', { count: rows.length })}
            </Typography>

            {actions.map((action) => (
                <Button
                    key={action.key}
                    variant="outlined"
                    size="small"
                    color={action.variant === 'danger' ? 'error' : 'primary'}
                    onClick={() => run(action)}
                >
                    {action.label}
                </Button>
            ))}

            <ConfirmDialog
                show={pending !== null}
                title={pending?.confirm?.title ?? ''}
                message={pending?.confirm?.message}
                danger={pending?.variant === 'danger'}
                onConfirm={confirm}
                onClose={() => setPending(null)}
            />
        </Stack>
    );
}
