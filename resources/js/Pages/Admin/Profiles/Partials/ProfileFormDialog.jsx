import FormDialog from '@/Components/FormDialog';
import PermissionGroups from '@/Components/PermissionGroups';
import { fieldProps } from '@/lib/form';
import { useTranslations } from '@/lib/i18n';
import { closeForm, FORM_SUBMIT } from '@/lib/listing';
import { useForm } from '@inertiajs/react';
import FormControl from '@mui/material/FormControl';
import FormHelperText from '@mui/material/FormHelperText';
import FormLabel from '@mui/material/FormLabel';
import TextField from '@mui/material/TextField';
import Typography from '@mui/material/Typography';

export default function ProfileFormDialog({ form }) {
    const __ = useTranslations();
    const { record, modules, selected } = form;
    const editing = form.mode === 'edit';

    const fields = useForm({
        name: record?.name ?? '',
        description: record?.description ?? '',
        permissions: selected,
    });

    const toggle = (id, checked) =>
        fields.setData(
            'permissions',
            checked
                ? [...fields.data.permissions, id]
                : fields.data.permissions.filter((item) => item !== id),
        );

    const submit = () => {
        if (editing) {
            fields.patch(route('admin.profiles.update', record.id), FORM_SUBMIT);
        } else {
            fields.post(route('admin.profiles.store'), FORM_SUBMIT);
        }
    };

    return (
        <FormDialog
            open
            title={editing ? __('Edit profile') : __('New profile')}
            processing={fields.processing}
            isDirty={fields.isDirty}
            onSubmit={submit}
            onClose={() => closeForm(route('admin.profiles.index'))}
        >
            <TextField
                {...fieldProps(fields, 'name', {
                    required: true,
                    htmlInput: { maxLength: 100 },
                })}
                label={__('Name')}
                autoFocus
            />

            <TextField
                {...fieldProps(fields, 'description', {
                    htmlInput: { maxLength: 255 },
                })}
                label={__('Description')}
            />

            {record?.is_administrator ? (
                <Typography variant="body2">
                    {__('This profile has full access to the system.')}
                </Typography>
            ) : (
                <FormControl
                    component="fieldset"
                    error={Boolean(fields.errors.permissions)}
                >
                    <FormLabel component="legend">{__('Permissions')}</FormLabel>

                    <PermissionGroups
                        modules={modules}
                        checked={fields.data.permissions}
                        onToggle={toggle}
                    />

                    {fields.errors.permissions && (
                        <FormHelperText>{fields.errors.permissions}</FormHelperText>
                    )}
                </FormControl>
            )}
        </FormDialog>
    );
}
