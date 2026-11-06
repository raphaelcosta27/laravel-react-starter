import ActionIconButton from '@/Components/ActionIconButton';
import ConfirmDialog from '@/Components/ConfirmDialog';
import SelectField from '@/Components/SelectField';
import { addressSummary } from '@/lib/address';
import { useTranslations } from '@/lib/i18n';
import { router, usePage } from '@inertiajs/react';
import DeleteOutlinedIcon from '@mui/icons-material/DeleteOutlined';
import EditIcon from '@mui/icons-material/Edit';
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import { useRef, useState } from 'react';
import AddressFormDialog from './AddressFormDialog';

export default function AddressesSection({
    addresses = [],
    defaultCountry = 'Brasil',
}) {
    const __ = useTranslations();
    const { errors } = usePage().props;
    const addButton = useRef(null);
    const removed = useRef(false);

    const [form, setForm] = useState(null);
    const [deleting, setDeleting] = useState(null);
    const [newMainId, setNewMainId] = useState('');
    const [processing, setProcessing] = useState(false);

    const others = addresses.filter(({ id }) => id !== deleting?.id);
    const choosesNext = Boolean(deleting?.is_main) && others.length > 0;

    const confirmDeletion = (address) => {
        setNewMainId('');
        setDeleting(address);
    };

    const remove = () => {
        setProcessing(true);

        router.delete(route('profile.addresses.destroy', deleting.id), {
            data: choosesNext ? { new_main_id: newMainId } : {},
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                removed.current = true;
                setDeleting(null);
            },
            onFinish: () => setProcessing(false),
        });
    };

    /**
     * O botão que abriu a confirmação sumiu com a linha. O foco só pode mudar
     * depois que o modal fecha, ou a devolução dele o tira daqui.
     */
    const focusAfterRemoval = () => {
        if (removed.current) {
            removed.current = false;
            addButton.current?.focus();
        }
    };

    const marks = (address) =>
        [
            ...(address.is_main ? [__('Main')] : []),
            ...(address.is_billing ? [__('Billing')] : []),
            ...(address.is_delivery ? [__('Delivery')] : []),
        ].join(' · ');

    return (
        <Stack component="section" spacing={3} sx={{ maxWidth: 576 }}>
            <header>
                <Typography component="h2" variant="h6">
                    {__('Addresses')}
                </Typography>

                <Typography variant="body2" color="text.secondary">
                    {__(
                        'Add, edit and delete your addresses. Only one can be the main address.',
                    )}
                </Typography>
            </header>

            {addresses.length === 0 ? (
                <Typography variant="body2">
                    {__('You have no addresses yet. Add the first one.')}
                </Typography>
            ) : (
                <Stack
                    component="ul"
                    spacing={2}
                    sx={{ listStyle: 'none', m: 0, p: 0 }}
                >
                    {addresses.map((address) => (
                        <Stack
                            key={address.id}
                            component="li"
                            direction="row"
                            spacing={1}
                            sx={{
                                alignItems: 'center',
                                justifyContent: 'space-between',
                            }}
                        >
                            <Box sx={{ minWidth: 0 }}>
                                <Typography sx={{ overflowWrap: 'anywhere' }}>
                                    {addressSummary(address)}
                                </Typography>

                                {marks(address) && (
                                    <Typography
                                        variant="body2"
                                        color="text.secondary"
                                    >
                                        {marks(address)}
                                    </Typography>
                                )}

                                {address.is_delivery && (
                                    <Typography
                                        variant="body2"
                                        color="text.secondary"
                                        sx={{ overflowWrap: 'anywhere' }}
                                    >
                                        {__('Receives: :name', {
                                            name: address.recipient_name,
                                        })}
                                    </Typography>
                                )}
                            </Box>

                            <Stack
                                role="group"
                                aria-label={addressSummary(address)}
                                direction="row"
                                sx={{ flexShrink: 0 }}
                            >
                                <ActionIconButton
                                    icon={EditIcon}
                                    label={__('Edit address')}
                                    description={__('Change the data of this address.')}
                                    onClick={() => setForm({ record: address })}
                                />

                                <ActionIconButton
                                    icon={DeleteOutlinedIcon}
                                    label={__('Delete address')}
                                    description={__('Remove this address from your list.')}
                                    onClick={() => confirmDeletion(address)}
                                />
                            </Stack>
                        </Stack>
                    ))}
                </Stack>
            )}

            <Box>
                <Button
                    ref={addButton}
                    variant="contained"
                    onClick={() => setForm({ record: null })}
                >
                    {__('Add address')}
                </Button>
            </Box>

            {form && (
                <AddressFormDialog
                    record={form.record}
                    first={addresses.length === 0}
                    defaultCountry={defaultCountry}
                    onClose={() => setForm(null)}
                />
            )}

            <ConfirmDialog
                show={deleting !== null}
                title={__('Delete address')}
                message={
                    choosesNext
                        ? __(
                              'This is your main address. Choose which one takes its place.',
                          )
                        : __('This address will no longer appear in your list.')
                }
                confirmLabel={__('Delete')}
                danger
                processing={processing || (choosesNext && newMainId === '')}
                onConfirm={remove}
                onClose={() => setDeleting(null)}
                onExited={focusAfterRemoval}
            >
                {choosesNext && (
                    <SelectField
                        id="new_main_id"
                        size="medium"
                        sx={{ mt: 2 }}
                        label={__('New main address')}
                        value={newMainId}
                        error={Boolean(errors?.new_main_id)}
                        helperText={errors?.new_main_id}
                        onChange={(event) => setNewMainId(event.target.value)}
                    >
                        <option value="">{__('Select')}</option>
                        {others.map((address) => (
                            <option key={address.id} value={address.id}>
                                {addressSummary(address)}
                            </option>
                        ))}
                    </SelectField>
                )}
            </ConfirmDialog>
        </Stack>
    );
}
