import ActionIconButton from '@/Components/ActionIconButton';
import ConfirmDialog from '@/Components/ConfirmDialog';
import { useTranslations } from '@/lib/i18n';
import { formatPhone } from '@/lib/phone';
import { router } from '@inertiajs/react';
import DeleteOutlinedIcon from '@mui/icons-material/DeleteOutlined';
import EditIcon from '@mui/icons-material/Edit';
import AddIcon from '@mui/icons-material/Add';
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Stack from '@mui/material/Stack';
import Typography from '@mui/material/Typography';
import { useRef, useState } from 'react';
import ContactFormDialog from './ContactFormDialog';

const display = ({ type, value }) =>
    type === 'phone' ? formatPhone(value) : value;

export default function ContactsSection({ contacts = [], relationships = [] }) {
    const __ = useTranslations();
    const addButton = useRef(null);
    const removed = useRef(false);

    const [form, setForm] = useState(null);
    const [deleting, setDeleting] = useState(null);
    const [processing, setProcessing] = useState(false);

    const remove = () => {
        setProcessing(true);

        router.delete(route('profile.contacts.destroy', deleting.id), {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                removed.current = true;
                setProcessing(false);
                setDeleting(null);
            },
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

    const details = (contact) =>
        [
            contact.type === 'phone' ? __('Phone') : __('Email'),
            ...(contact.is_whatsapp ? [__('WhatsApp')] : []),
        ].join(' · ');

    return (
        <Stack component="section" spacing={3} sx={{ maxWidth: 576 }}>
            <header>
                <Typography component="h2" variant="h6">
                    {__('Contacts')}
                </Typography>

                <Typography variant="body2" color="text.secondary">
                    {__(
                        'Phones and emails where you can be reached. They do not change the email you use to log in.',
                    )}
                </Typography>
            </header>

            {contacts.length === 0 ? (
                <Typography variant="body2">
                    {__('You have no contacts yet. Add a phone or an email.')}
                </Typography>
            ) : (
                <Stack
                    component="ul"
                    spacing={2}
                    sx={{ listStyle: 'none', m: 0, p: 0 }}
                >
                    {contacts.map((contact) => (
                        <Stack
                            key={contact.id}
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
                                    {display(contact)}
                                </Typography>

                                <Typography variant="body2" color="text.secondary">
                                    {details(contact)}
                                </Typography>

                                {contact.is_emergency && (
                                    <Typography
                                        variant="body2"
                                        color="text.secondary"
                                        sx={{ overflowWrap: 'anywhere' }}
                                    >
                                        {__('Emergency: :name (:relationship)', {
                                            name: contact.contact_name,
                                            relationship: contact.relationship_label,
                                        })}
                                    </Typography>
                                )}
                            </Box>

                            <Stack
                                role="group"
                                aria-label={display(contact)}
                                direction="row"
                                sx={{ flexShrink: 0 }}
                            >
                                <ActionIconButton
                                    icon={EditIcon}
                                    label={__('Edit contact')}
                                    description={__('Change the data of this contact.')}
                                    onClick={() => setForm({ record: contact })}
                                />

                                <ActionIconButton
                                    icon={DeleteOutlinedIcon}
                                    label={__('Delete contact')}
                                    description={__('Remove this contact from your list.')}
                                    onClick={() => setDeleting(contact)}
                                />
                            </Stack>
                        </Stack>
                    ))}
                </Stack>
            )}

            <Box>
                <Button
                    startIcon={<AddIcon />}
                    ref={addButton}
                    variant="contained"
                    onClick={() => setForm({ record: null })}
                >
                    {__('Add contact')}
                </Button>
            </Box>

            {form && (
                <ContactFormDialog
                    record={form.record}
                    relationships={relationships}
                    onClose={() => setForm(null)}
                />
            )}

            <ConfirmDialog
                show={deleting !== null}
                title={__('Delete contact')}
                message={__('This contact will no longer appear in your list.')}
                confirmLabel={__('Delete')}
                danger
                processing={processing}
                onConfirm={remove}
                onClose={() => setDeleting(null)}
                onExited={focusAfterRemoval}
            />
        </Stack>
    );
}
