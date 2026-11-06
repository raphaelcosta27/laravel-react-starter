import FormDialog from '@/Components/FormDialog';
import SelectField from '@/Components/SelectField';
import { fieldProps } from '@/lib/form';
import { useTranslations } from '@/lib/i18n';
import { closeForm, FORM_SUBMIT } from '@/lib/listing';
import { useForm } from '@inertiajs/react';
import TextField from '@mui/material/TextField';

export default function PermissionFormDialog({ form, modules = [] }) {
    const __ = useTranslations();
    const { record } = form;
    const editing = form.mode === 'edit';

    const fields = useForm({
        title: record?.title ?? '',
        code: record?.code ?? '',
        description: record?.description ?? '',
        module: record?.module ?? '',
    });

    const submit = () => {
        if (editing) {
            fields.patch(route('admin.permissions.update', record.id), FORM_SUBMIT);
        } else {
            fields.post(route('admin.permissions.store'), FORM_SUBMIT);
        }
    };

    return (
        <FormDialog
            open
            title={editing ? __('Edit permission') : __('New permission')}
            processing={fields.processing}
            isDirty={fields.isDirty}
            onSubmit={submit}
            onClose={() => closeForm(route('admin.permissions.index'))}
        >
            <TextField
                {...fieldProps(fields, 'title', {
                    required: true,
                    htmlInput: { maxLength: 150 },
                })}
                label={__('Title')}
                autoFocus
            />

            <TextField
                {...fieldProps(fields, 'code', {
                    required: true,
                    htmlInput: { maxLength: 100, readOnly: editing },
                })}
                label={__('Code')}
                helperText={
                    fields.errors.code ??
                    (editing
                        ? __('The code cannot be changed after creation.')
                        : __('Lowercase letters without accents, numbers and underscores, starting with a letter.'))
                }
            />

            <TextField
                {...fieldProps(fields, 'description', {
                    required: true,
                    htmlInput: { maxLength: 255 },
                })}
                label={__('Description')}
            />

            <SelectField
                id="permission-module"
                label={__('Module')}
                required={!record?.is_system}
                disabled={Boolean(record?.is_system)}
                value={fields.data.module}
                error={Boolean(fields.errors.module)}
                helperText={
                    fields.errors.module ??
                    (record?.is_system
                        ? __('The module of a system permission cannot be changed.')
                        : undefined)
                }
                onChange={(event) => fields.setData('module', event.target.value)}
            >
                <option value="">{__('Select')}</option>
                {modules.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </SelectField>
        </FormDialog>
    );
}
