import EnvironmentForm, {
    environmentValues,
    focusFirstError,
} from '@/Components/EnvironmentForm';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useTranslations } from '@/lib/i18n';
import { Head, useForm } from '@inertiajs/react';
import SaveOutlinedIcon from '@mui/icons-material/SaveOutlined';
import Alert from '@mui/material/Alert';
import Button from '@mui/material/Button';
import Paper from '@mui/material/Paper';
import Stack from '@mui/material/Stack';

export default function Index({ groups, version, writable, editable }) {
    const __ = useTranslations();
    const readOnly = !editable || !writable;

    const form = useForm({ values: environmentValues(groups), version });

    const submit = (event) => {
        event.preventDefault();

        form.patch(route('admin.environment.update'), {
            preserveScroll: true,
            onError: focusFirstError,
        });
    };

    return (
        <AuthenticatedLayout title={__('Environment')}>
            <Head title={__('Environment')} />

            <Paper sx={{ p: { xs: 2, sm: 4 } }}>
                <Stack
                    component="form"
                    spacing={3}
                    sx={{ maxWidth: 720 }}
                    onSubmit={submit}
                >
                    {!editable && (
                        <Alert severity="info">
                            {__('Saving is turned off. This page is read only.')}
                        </Alert>
                    )}

                    {!writable && (
                        <Alert severity="warning">
                            {__(
                                'The environment file cannot be written. Review its permissions on the server.',
                            )}
                        </Alert>
                    )}

                    {form.errors.environment && (
                        <Alert severity="error">{form.errors.environment}</Alert>
                    )}

                    <Alert severity="info">
                        {__(
                            'Changes to the address or port may require restarting the server. Variables starting with VITE_ require a new build.',
                        )}
                    </Alert>

                    <EnvironmentForm
                        groups={groups}
                        form={form}
                        readOnly={readOnly}
                    />

                    {!readOnly && (
                        <Stack direction="row">
                            <Button
                                startIcon={<SaveOutlinedIcon />}
                                type="submit"
                                variant="contained"
                                disabled={form.processing}
                            >
                                {__('Save')}
                            </Button>
                        </Stack>
                    )}
                </Stack>
            </Paper>
        </AuthenticatedLayout>
    );
}
