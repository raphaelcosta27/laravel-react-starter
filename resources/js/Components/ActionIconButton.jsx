import { visuallyHidden } from '@/lib/visuallyHidden';
import { Link } from '@inertiajs/react';
import Box from '@mui/material/Box';
import IconButton from '@mui/material/IconButton';
import Tooltip from '@mui/material/Tooltip';
import Typography from '@mui/material/Typography';
import { useId, useRef } from 'react';

const LONG_PRESS = 500;

export default function ActionIconButton({
    icon: Icon,
    label,
    description,
    href,
    disabled = false,
    color = 'default',
    onClick,
    ...props
}) {
    const descriptionId = useId();
    const touchedAt = useRef(null);

    /**
     * O toque longo só mostra a dica: a ação fica para o toque curto.
     */
    const click = (event) => {
        const pressed = touchedAt.current;

        touchedAt.current = null;

        if (pressed !== null && Date.now() - pressed >= LONG_PRESS) {
            event.preventDefault();

            return;
        }

        onClick?.(event);
    };

    const button = (
        <IconButton
            size="small"
            color={color}
            disabled={disabled}
            aria-label={label}
            aria-describedby={description ? descriptionId : undefined}
            onTouchStart={() => {
                touchedAt.current = Date.now();
            }}
            onClick={click}
            {...(href ? { component: Link, href } : {})}
            {...props}
        >
            <Icon fontSize="small" />
        </IconButton>
    );

    return (
        <>
            <Tooltip
                describeChild
                enterTouchDelay={LONG_PRESS}
                leaveTouchDelay={3000}
                title={
                    <>
                        <Typography variant="caption" component="p" fontWeight={700}>
                            {label}
                        </Typography>

                        {description && (
                            <Typography variant="caption" component="p">
                                {description}
                            </Typography>
                        )}
                    </>
                }
            >
                {disabled ? (
                    <Box component="span" sx={{ display: 'inline-flex' }}>
                        {button}
                    </Box>
                ) : (
                    button
                )}
            </Tooltip>

            {description && (
                <span id={descriptionId} style={visuallyHidden}>
                    {description}
                </span>
            )}
        </>
    );
}
