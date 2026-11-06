import FormDialog from '@/Components/FormDialog';
import SelectField from '@/Components/SelectField';
import { fieldProps } from '@/lib/form';
import { useTranslations } from '@/lib/i18n';
import { closeForm, FORM_SUBMIT } from '@/lib/listing';
import { useForm } from '@inertiajs/react';
import TextField from '@mui/material/TextField';

export default function UserFormDialog({ form }) {
    const __ = useTranslations();
    const { record, profileOptions, canChangeProfile, canChangeEmail } = form;
    const editing = form.mode === 'edit';

    const fields = useForm({
        name: record?.name ?? '',
        email: record?.email ?? '',
        profile_id: record?.profile_id ? String(record.profile_id) : '',
    });

    const submit = () => {
        if (editing) {
            fields.patch(route('admin.users.update', record.id), FORM_SUBMIT);
        } else {
            fields.post(route('admin.users.store'), FORM_SUBMIT);
        }
    };

    return (
        <FormDialog
            open
            title={editing ? __('Edit user') : __('New user')}
            processing={fields.processing}
            isDirty={fields.isDirty}
            onSubmit={submit}
            onClose={() => closeForm(route('admin.users.index'))}
        >
            <TextField
                {...fieldProps(fields, 'name', {
                    kind: 'person',
                    required: true,
                    htmlInput: { maxLength: 255 },
                })}
                label={__('Name')}
                autoFocus
            />

            <TextField
                {...fieldProps(fields, 'email', {
                    kind: 'email',
                    required: true,
                    htmlInput: { maxLength: 255 },
                })}
                type="email"
                label={__('Email')}
                disabled={!canChangeEmail}
                helperText={
                    fields.errors.email ??
                    (canChangeEmail
                        ? undefined
                        : __('Only administrators can change the email of an account.'))
                }
            />

            <SelectField
                id="profile_id"
                name="profile_id"
                label={__('Profile')}
                required
                disabled={!canChangeProfile}
                value={fields.data.profile_id}
                error={Boolean(fields.errors.profile_id)}
                helperText={fields.errors.profile_id}
                onChange={(event) => fields.setData('profile_id', event.target.value)}
            >
                <option value="">{__('Select')}</option>
                {profileOptions.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </SelectField>
        </FormDialog>
    );
}
