import FieldIcon from '@/Components/FieldIcon';
import AddressFields from '@/Components/AddressFields';
import FormDialog from '@/Components/FormDialog';
import { formatPostalCode, isBrazil } from '@/lib/address';
import { fieldProps } from '@/lib/form';
import { useTranslations } from '@/lib/i18n';
import { useForm } from '@inertiajs/react';
import LocalShippingOutlinedIcon from '@mui/icons-material/LocalShippingOutlined';
import Checkbox from '@mui/material/Checkbox';
import FormControlLabel from '@mui/material/FormControlLabel';
import FormHelperText from '@mui/material/FormHelperText';
import TextField from '@mui/material/TextField';

export default function AddressFormDialog({
    record = null,
    first = false,
    defaultCountry = 'Brasil',
    onClose,
}) {
    const __ = useTranslations();
    const country = record?.country ?? defaultCountry;
    const lockedMain = first || Boolean(record?.is_main);

    const fields = useForm({
        postal_code: isBrazil(country)
            ? formatPostalCode(record?.postal_code)
            : (record?.postal_code ?? ''),
        street: record?.street ?? '',
        number: record?.number ?? '',
        complement: record?.complement ?? '',
        reference: record?.reference ?? '',
        neighborhood: record?.neighborhood ?? '',
        city: record?.city ?? '',
        state: record?.state ?? '',
        country,
        is_main: lockedMain,
        is_billing: record?.is_billing ?? false,
        is_delivery: record?.is_delivery ?? false,
        recipient_name: record?.recipient_name ?? '',
    });

    const submit = () => {
        const options = {
            preserveScroll: true,
            preserveState: true,
            onSuccess: onClose,
        };

        if (record) {
            fields.patch(route('profile.addresses.update', record.id), options);
        } else {
            fields.post(route('profile.addresses.store'), options);
        }
    };

    const checkbox = (name, label, props = {}) => (
        <FormControlLabel
            label={label}
            sx={{ mt: -1 }}
            control={
                <Checkbox
                    name={name}
                    checked={fields.data[name]}
                    onChange={(event) =>
                        fields.setData(name, event.target.checked)
                    }
                    {...props}
                />
            }
        />
    );

    return (
        <FormDialog
            open
            title={record ? __('Edit address') : __('Add address')}
            processing={fields.processing}
            isDirty={fields.isDirty}
            onSubmit={submit}
            onClose={onClose}
        >
            <AddressFields form={fields} />

            {checkbox('is_main', __('Main address'), { disabled: lockedMain })}

            {record?.is_main && (
                <FormHelperText sx={{ mt: -2 }}>
                    {__('To change the main address, mark another one as main.')}
                </FormHelperText>
            )}

            {checkbox('is_billing', __('Billing address'))}

            {checkbox('is_delivery', __('Delivery address'))}

            {fields.data.is_delivery && (
                <TextField
                    {...fieldProps(fields, 'recipient_name', {
                        required: true,
                        htmlInput: { maxLength: 150 },
                        adornment: <FieldIcon icon={LocalShippingOutlinedIcon} />,
                    })}
                    label={__('Address owner (who will receive)')}
                />
            )}
        </FormDialog>
    );
}
