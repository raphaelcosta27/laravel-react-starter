import { useTranslations } from '@/lib/i18n';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import Accordion from '@mui/material/Accordion';
import AccordionDetails from '@mui/material/AccordionDetails';
import AccordionSummary from '@mui/material/AccordionSummary';
import Alert from '@mui/material/Alert';
import Box from '@mui/material/Box';
import Checkbox from '@mui/material/Checkbox';
import FormControlLabel from '@mui/material/FormControlLabel';
import Typography from '@mui/material/Typography';
import { useId } from 'react';

/**
 * Lista de permissões por módulo, para o formulário de perfil e o modal do
 * usuário. O que muda entre eles vem em `renderExtra` e `renderNotice`.
 */
export default function PermissionGroups({
    modules,
    checked,
    onToggle,
    renderExtra,
    renderNotice,
}) {
    const __ = useTranslations();
    const baseId = useId();

    return (
        <div>
            {modules.map((module) => {
                const marked = module.permissions.filter(({ id }) =>
                    checked.includes(id),
                ).length;

                return (
                    <Accordion key={module.key} disableGutters variant="outlined">
                        <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                            <Box
                                sx={{
                                    alignItems: 'center',
                                    display: 'flex',
                                    flexGrow: 1,
                                    gap: 1,
                                    justifyContent: 'space-between',
                                    pr: 1,
                                }}
                            >
                                <Typography variant="subtitle2" component="span">
                                    {module.label}
                                </Typography>

                                <Typography
                                    variant="body2"
                                    component="span"
                                    sx={{ color: 'text.secondary' }}
                                >
                                    {__(':marked of :total', {
                                        marked,
                                        total: module.permissions.length,
                                    })}
                                </Typography>
                            </Box>
                        </AccordionSummary>

                        <AccordionDetails sx={{ display: 'grid', gap: 1 }}>
                            {module.notice && (
                                <Alert severity="info">{module.notice}</Alert>
                            )}

                            {renderNotice?.(module)}

                            {module.permissions.map((permission) => {
                                const descriptionId = `${baseId}-${permission.id}`;
                                const isChecked = checked.includes(permission.id);

                                return (
                                    <Box
                                        key={permission.id}
                                        sx={{
                                            alignItems: 'center',
                                            display: 'flex',
                                            flexWrap: 'wrap',
                                            gap: 1,
                                            justifyContent: 'space-between',
                                        }}
                                    >
                                        <Box sx={{ minWidth: 0 }}>
                                            <FormControlLabel
                                                label={permission.title}
                                                sx={{ mr: 0 }}
                                                control={
                                                    <Checkbox
                                                        disabled={!permission.available}
                                                        checked={isChecked}
                                                        slotProps={{
                                                            input: {
                                                                'aria-describedby': descriptionId,
                                                            },
                                                        }}
                                                        onChange={(event) =>
                                                            onToggle(permission.id, event.target.checked)
                                                        }
                                                    />
                                                }
                                            />

                                            <Typography
                                                id={descriptionId}
                                                variant="caption"
                                                component="p"
                                                sx={{ color: 'text.secondary', pl: 4 }}
                                            >
                                                {permission.available
                                                    ? permission.description
                                                    : __('You do not have this permission, so you cannot change it.')}
                                            </Typography>
                                        </Box>

                                        {renderExtra?.(permission, isChecked)}
                                    </Box>
                                );
                            })}
                        </AccordionDetails>
                    </Accordion>
                );
            })}
        </div>
    );
}
