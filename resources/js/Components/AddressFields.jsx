import FieldIcon from '@/Components/FieldIcon';
import SelectField from '@/Components/SelectField';
import {
    BRAZILIAN_STATES,
    formatPostalCode,
    isBrazil,
    NO_NUMBER,
} from '@/lib/address';
import { fieldProps } from '@/lib/form';
import { useTranslations } from '@/lib/i18n';
import ApartmentOutlinedIcon from '@mui/icons-material/ApartmentOutlined';
import HolidayVillageOutlinedIcon from '@mui/icons-material/HolidayVillageOutlined';
import LocationCityOutlinedIcon from '@mui/icons-material/LocationCityOutlined';
import MapOutlinedIcon from '@mui/icons-material/MapOutlined';
import MarkunreadMailboxOutlinedIcon from '@mui/icons-material/MarkunreadMailboxOutlined';
import PlaceOutlinedIcon from '@mui/icons-material/PlaceOutlined';
import PublicOutlinedIcon from '@mui/icons-material/PublicOutlined';
import SignpostOutlinedIcon from '@mui/icons-material/SignpostOutlined';
import TagOutlinedIcon from '@mui/icons-material/TagOutlined';
import Checkbox from '@mui/material/Checkbox';
import FormControlLabel from '@mui/material/FormControlLabel';
import TextField from '@mui/material/TextField';
import Typography from '@mui/material/Typography';
import { useEffect, useRef, useState } from 'react';

const FILLED_FIELDS = ['street', 'neighborhood', 'city', 'state'];

/**
 * Campos de endereço para qualquer `useForm` que tenha estes nomes. Não conhece
 * as regras de quem o usa. Falhas da busca nunca impedem o preenchimento manual.
 */
export default function AddressFields({ form }) {
    const __ = useTranslations();

    const latest = useRef(form);
    const search = useRef(null);
    const numberInput = useRef(null);
    const [message, setMessage] = useState('');

    latest.current = form;

    const brazil = isBrazil(form.data.country);

    const cancelSearch = () => {
        search.current?.abort();
        search.current = null;
    };

    useEffect(() => cancelSearch, []);

    /**
     * Um campo alterado durante a busca é da pessoa: só muda o que continua como
     * estava no início. A busca seguinte cancela esta.
     */
    const lookup = async (postalCode) => {
        cancelSearch();

        const controller = new AbortController();
        const before = { ...latest.current.data };

        search.current = controller;
        setMessage(__('Searching postal code...'));

        try {
            const response = await fetch(route('postal-code.show', postalCode), {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            });

            if (response.status === 404) {
                setMessage(
                    __('Postal code not found. Fill in the address manually.'),
                );

                return;
            }

            if (!response.ok) {
                throw new Error(response.statusText);
            }

            const found = await response.json();

            if (controller.signal.aborted) {
                return;
            }

            FILLED_FIELDS.filter(
                (field) =>
                    found[field] &&
                    latest.current.data[field] === before[field],
            ).forEach((field) => latest.current.setData(field, found[field]));

            setMessage(
                __('Address filled in from the postal code. Review the fields.'),
            );

            if (document.activeElement?.id === 'postal_code') {
                numberInput.current?.focus();
            }
        } catch (error) {
            if (!controller.signal.aborted) {
                setMessage(
                    __(
                        'The postal code search is unavailable. Fill in the address manually.',
                    ),
                );
            }
        }
    };

    const changePostalCode = (event) => {
        if (!brazil) {
            form.setData('postal_code', event.target.value);

            return;
        }

        const value = formatPostalCode(event.target.value);

        form.setData('postal_code', value);

        if (value.length === 9) {
            lookup(value.replace('-', ''));
        } else {
            cancelSearch();
            setMessage('');
        }
    };

    const changeCountry = (event) => {
        const country = event.target.value;

        form.setData('country', country);

        if (isBrazil(country) !== brazil) {
            cancelSearch();
            setMessage('');
            form.setData('state', '');
            form.setData(
                'postal_code',
                isBrazil(country)
                    ? formatPostalCode(form.data.postal_code)
                    : form.data.postal_code,
            );
        }
    };

    const text = (
        name,
        label,
        { required = false, maxLength, autoComplete, icon },
    ) => {
        const props = fieldProps(form, name, {
            required,
            htmlInput: { maxLength, autoComplete },
            adornment: <FieldIcon icon={icon} />,
        });

        return <TextField {...props} label={label} />;
    };

    return (
        <>
            <TextField
                {...fieldProps(form, 'postal_code', {
                    required: true,
                    htmlInput: {
                        maxLength: brazil ? 9 : 20,
                        autoComplete: 'postal-code',
                        ...(brazil ? { inputMode: 'numeric' } : {}),
                    },
                    adornment: <FieldIcon icon={MarkunreadMailboxOutlinedIcon} />,
                })}
                label={__('Postal code')}
                onChange={changePostalCode}
            />

            <Typography
                role="status"
                aria-live="polite"
                variant="body2"
                color="text.secondary"
                sx={{ mt: message ? 0 : -2 }}
            >
                {message}
            </Typography>

            {text('street', __('Street'), {
                required: true,
                maxLength: 255,
                autoComplete: 'address-line1',
                icon: SignpostOutlinedIcon,
            })}

            <TextField
                {...fieldProps(form, 'number', {
                    required: true,
                    htmlInput: { maxLength: 20 },
                    adornment: <FieldIcon icon={TagOutlinedIcon} />,
                })}
                inputRef={numberInput}
                label={__('Number')}
                disabled={form.data.number === NO_NUMBER}
            />

            <FormControlLabel
                label={__('No number')}
                sx={{ mt: -1 }}
                control={
                    <Checkbox
                        name="no_number"
                        checked={form.data.number === NO_NUMBER}
                        onChange={(event) =>
                            form.setData(
                                'number',
                                event.target.checked ? NO_NUMBER : '',
                            )
                        }
                    />
                }
            />

            {text('complement', __('Complement'), {
                maxLength: 100,
                autoComplete: 'address-line2',
                icon: ApartmentOutlinedIcon,
            })}

            {text('reference', __('Reference point'), {
                maxLength: 150,
                icon: PlaceOutlinedIcon,
            })}

            {text('neighborhood', __('Neighborhood'), {
                required: true,
                maxLength: 100,
                autoComplete: 'address-level3',
                icon: HolidayVillageOutlinedIcon,
            })}

            {text('city', __('City'), {
                required: true,
                maxLength: 100,
                autoComplete: 'address-level2',
                icon: LocationCityOutlinedIcon,
            })}

            {brazil ? (
                <SelectField
                    {...fieldProps(form, 'state', {
                        adornment: <FieldIcon icon={MapOutlinedIcon} />,
                    })}
                    size="medium"
                    label={__('State')}
                    required
                >
                    <option value="">{__('Select')}</option>
                    {BRAZILIAN_STATES.map(([value, label]) => (
                        <option key={value} value={value}>
                            {label}
                        </option>
                    ))}
                </SelectField>
            ) : (
                text('state', __('State'), {
                    required: true,
                    maxLength: 100,
                    autoComplete: 'address-level1',
                    icon: MapOutlinedIcon,
                })
            )}

            <TextField
                {...fieldProps(form, 'country', {
                    required: true,
                    htmlInput: { maxLength: 100, autoComplete: 'country-name' },
                    adornment: <FieldIcon icon={PublicOutlinedIcon} />,
                })}
                label={__('Country')}
                onChange={changeCountry}
            />
        </>
    );
}
