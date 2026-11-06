import StatusAnnouncer from '@/Components/StatusAnnouncer';
import { useTranslations } from '@/lib/i18n';
import { visuallyHidden } from '@/lib/visuallyHidden';
import { usePage } from '@inertiajs/react';
import CheckCircleIcon from '@mui/icons-material/CheckCircle';
import RadioButtonUncheckedIcon from '@mui/icons-material/RadioButtonUnchecked';
import Box from '@mui/material/Box';
import LinearProgress from '@mui/material/LinearProgress';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';

function strength(met, total) {
    if (met === total) {
        return { level: 'Strong', color: 'success' };
    }

    return met > total / 2
        ? { level: 'Medium', color: 'warning' }
        : { level: 'Weak', color: 'error' };
}

/**
 * Barra de força de uma senha. Os critérios vêm do servidor, que os usa para
 * validar: aqui eles só orientam quem digita.
 */
export default function PasswordStrength({ value }) {
    const __ = useTranslations();
    const { passwordCriteria } = usePage().props;

    const criteria = passwordCriteria.map((criterion) => ({
        ...criterion,
        met: new RegExp(criterion.pattern, 'su').test(value),
    }));
    const total = criteria.length;
    const met = criteria.filter((criterion) => criterion.met).length;

    const { level, color } = strength(met, total);
    const levelText = __(value ? level : 'Not informed');

    return (
        <Stack spacing={1}>
            <LinearProgress
                variant="determinate"
                value={(met / total) * 100}
                color={value ? color : 'inherit'}
                aria-label={__('Password strength')}
                aria-valuetext={levelText}
            />

            <Typography variant="caption" color="text.secondary">
                {__('Password strength: :level', { level: levelText })}
            </Typography>

            <Box component="ul" sx={{ listStyle: 'none', m: 0, p: 0 }}>
                {criteria.map((criterion) => (
                    <Stack
                        key={criterion.key}
                        component="li"
                        direction="row"
                        spacing={1}
                        sx={{ alignItems: 'center' }}
                    >
                        {criterion.met ? (
                            <CheckCircleIcon fontSize="small" color="success" />
                        ) : (
                            <RadioButtonUncheckedIcon
                                fontSize="small"
                                color="action"
                            />
                        )}

                        <Typography variant="body2">{criterion.label}</Typography>

                        <Box component="span" sx={visuallyHidden}>
                            {__(criterion.met ? 'Met' : 'Pending')}
                        </Box>
                    </Stack>
                ))}
            </Box>

            <StatusAnnouncer
                visuallyHidden
                message={
                    value
                        ? __(
                              'Password strength: :level. :met of :total criteria met.',
                              { level: levelText, met, total },
                          )
                        : ''
                }
            />
        </Stack>
    );
}
