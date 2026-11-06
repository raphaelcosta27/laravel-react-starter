import { requestJson } from '@/lib/http';
import { useTranslations } from '@/lib/i18n';
import { FORM_VISIT, withListingQuery } from '@/lib/listing';
import { router } from '@inertiajs/react';
import CloseIcon from '@mui/icons-material/Close';
import Autocomplete from '@mui/material/Autocomplete';
import Button from '@mui/material/Button';
import Dialog from '@mui/material/Dialog';
import DialogActions from '@mui/material/DialogActions';
import DialogContent from '@mui/material/DialogContent';
import DialogTitle from '@mui/material/DialogTitle';
import TextField from '@mui/material/TextField';
import { useEffect, useId, useState } from 'react';

const WAIT = 300;

export default function UserPickerDialog({ open, onClose }) {
    const __ = useTranslations();
    const titleId = useId();
    const [search, setSearch] = useState('');
    const [options, setOptions] = useState([]);
    const [loading, setLoading] = useState(false);
    const [failed, setFailed] = useState(false);

    useEffect(() => {
        if (!open) {
            return undefined;
        }

        let current = true;

        const timer = setTimeout(() => {
            setLoading(true);

            requestJson(route('admin.permissions.users.search', { search }))
                .then((users) => {
                    if (current) {
                        setOptions(users);
                        setFailed(false);
                    }
                })
                .catch(() => current && setFailed(true))
                .finally(() => current && setLoading(false));
        }, WAIT);

        return () => {
            current = false;
            clearTimeout(timer);
        };
    }, [open, search]);

    const choose = (user) => {
        if (user) {
            onClose();
            router.visit(
                withListingQuery(route('admin.permissions.users.edit', user.id)),
                FORM_VISIT,
            );
        }
    };

    return (
        <Dialog
            open={open}
            fullWidth
            maxWidth="xs"
            aria-labelledby={titleId}
            onClose={onClose}
        >
            <DialogTitle id={titleId}>{__('User permissions')}</DialogTitle>

            <DialogContent>
                <Autocomplete
                    options={options}
                    loading={loading}
                    filterOptions={(items) => items}
                    getOptionLabel={(user) => `${user.name} (${user.email})`}
                    isOptionEqualToValue={(option, value) => option.id === value.id}
                    noOptionsText={__('No users found.')}
                    loadingText={__('Searching...')}
                    onInputChange={(event, value) => setSearch(value)}
                    onChange={(event, user) => choose(user)}
                    renderInput={(params) => (
                        <TextField
                            {...params}
                            autoFocus
                            label={__('User')}
                            error={failed}
                            helperText={failed ? __('The search failed. Try again.') : undefined}
                        />
                    )}
                />
            </DialogContent>

            <DialogActions>
                <Button startIcon={<CloseIcon />} onClick={onClose}>{__('Cancel')}</Button>
            </DialogActions>
        </Dialog>
    );
}
