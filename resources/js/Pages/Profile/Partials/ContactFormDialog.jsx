import FieldIcon from '@/Components/FieldIcon';
import FormDialog from '@/Components/FormDialog';
import SelectField from '@/Components/SelectField';
import { fieldProps } from '@/lib/form';
import { useTranslations } from '@/lib/i18n';
import { formatPhone } from '@/lib/phone';
import { useForm } from '@inertiajs/react';
import ContactPhoneOutlinedIcon from '@mui/icons-material/ContactPhoneOutlined';
import EmailOutlinedIcon from '@mui/icons-material/EmailOutlined';
import FamilyRestroomOutlinedIcon from '@mui/icons-material/FamilyRestroomOutlined';
import PersonOutlinedIcon from '@mui/icons-material/PersonOutlined';
import PhoneOutlinedIcon from '@mui/icons-material/PhoneOutlined';
import Checkbox from '@mui/material/Checkbox';
import FormControlLabel from '@mui/material/FormControlLabel';
import TextField from '@mui/material/TextField';

export default function ContactFormDialog({
    record = null,
    relationships = [],
    onClose,
}) {
    const __ = useTranslations();

    const fields = useForm({
        type: record?.type ?? 'phone',
        value:
            record?.type === 'email' ? record.value : formatPhone(record?.value),
        is_whatsapp: record?.is_whatsapp ?? false,
        is_emergency: record?.is_emergency ?? false,
        relationship: relationships.some(
            ({ value }) => value === record?.relationship,
        )
            ? record.relationship
            : '',
        contact_name: record?.contact_name ?? '',
    });

    const isPhone = fields.data.type === 'phone';

    const changeType = (event) => {
        fields.setData('type', event.target.value);
        fields.setData('value', '');
        fields.setData('is_whatsapp', false);
    };

    const submit = () => {
        const options = {
            preserveScroll: true,
            preserveState: true,
            onSuccess: onClose,
        };

        if (record) {
            fields.patch(route('profile.contacts.update', record.id), options);
        } else {
            fields.post(route('profile.contacts.store'), options);
        }
    };

    const checkbox = (name, label) => (
        <FormControlLabel
            label={label}
            control={
                <Checkbox
                    name={name}
                    checked={fields.data[name]}
                    onChange={(event) =>
                        fields.setData(name, event.target.checked)
                    }
                />
            }
        />
    );

    return (
        <FormDialog
            open
            title={record ? __('Edit contact') : __('Add contact')}
            processing={fields.processing}
            isDirty={fields.isDirty}
            onSubmit={submit}
            onClose={onClose}
        >
            <SelectField
                {...fieldProps(fields, 'type', {
                    adornment: <FieldIcon icon={ContactPhoneOutlinedIcon} />,
                })}
                size="medium"
                label={__('Contact type')}
                required
                autoFocus
                onChange={changeType}
            >
                <option value="phone">{__('Phone')}</option>
                <option value="email">{__('Email')}</option>
            </SelectField>

            <TextField
                {...fieldProps(fields, 'value', {
                    required: true,
                    htmlInput: isPhone
                        ? { maxLength: 15, inputMode: 'tel' }
                        : { maxLength: 255 },
                    adornment: (
                        <FieldIcon
                            icon={isPhone ? PhoneOutlinedIcon : EmailOutlinedIcon}
                        />
                    ),
                })}
                type={isPhone ? 'tel' : 'email'}
                label={isPhone ? __('Phone') : __('Email')}
                onChange={(event) =>
                    fields.setData(
                        'value',
                        isPhone
                            ? formatPhone(event.target.value)
                            : event.target.value,
                    )
                }
            />

            {isPhone && checkbox('is_whatsapp', __('This number has WhatsApp'))}

            {checkbox('is_emergency', __('Emergency contact'))}

            {fields.data.is_emergency && (
                <>
                    <SelectField
                        {...fieldProps(fields, 'relationship', {
                            adornment: <FieldIcon icon={FamilyRestroomOutlinedIcon} />,
                        })}
                        size="medium"
                        label={__('Relationship')}
                        required
                    >
                        <option value="">{__('Select')}</option>
                        {relationships.map(({ value, label }) => (
                            <option key={value} value={value}>
                                {label}
                            </option>
                        ))}
                    </SelectField>

                    <TextField
                        {...fieldProps(fields, 'contact_name', {
                            required: true,
                            htmlInput: { maxLength: 150 },
                            adornment: <FieldIcon icon={PersonOutlinedIcon} />,
                        })}
                        label={__('Contact name')}
                    />
                </>
            )}
        </FormDialog>
    );
}
