import FieldIcon from '@/Components/FieldIcon';
import BadgeOutlinedIcon from '@mui/icons-material/BadgeOutlined';
import EmailOutlinedIcon from '@mui/icons-material/EmailOutlined';
import KeyOutlinedIcon from '@mui/icons-material/KeyOutlined';
import LabelOutlinedIcon from '@mui/icons-material/LabelOutlined';
import LockOutlinedIcon from '@mui/icons-material/LockOutlined';
import NotesIcon from '@mui/icons-material/Notes';
import PersonOutlinedIcon from '@mui/icons-material/PersonOutlined';
import PhoneOutlinedIcon from '@mui/icons-material/PhoneOutlined';
import PinOutlinedIcon from '@mui/icons-material/PinOutlined';
import { createElement } from 'react';

/** Ícone e teclado de cada tipo de campo. */
const KINDS = {
    email: { icon: EmailOutlinedIcon, type: 'email', inputMode: 'email' },
    password: { icon: LockOutlinedIcon, type: 'password' },
    phone: { icon: PhoneOutlinedIcon, type: 'tel', inputMode: 'tel' },
    person: { icon: PersonOutlinedIcon },
    pin: { icon: PinOutlinedIcon, inputMode: 'numeric' },
    key: { icon: KeyOutlinedIcon },
    label: { icon: LabelOutlinedIcon },
    document: { icon: BadgeOutlinedIcon, inputMode: 'numeric' },
    description: { icon: NotesIcon },
};

/**
 * Propriedades de um `TextField` ligado a um formulário do Inertia.
 * O obrigatório vai ao campo nativo, sem asterisco no rótulo.
 */
export function fieldProps(
    form,
    name,
    { required = false, htmlInput = {}, adornment = null, kind = null } = {},
) {
    const error = form.errors[name];
    const { icon, type, inputMode } = KINDS[kind] ?? {};
    const start = adornment ?? (icon ? createElement(FieldIcon, { icon }) : null);
    const attributes = {
        ...(inputMode ? { inputMode } : {}),
        ...htmlInput,
        ...(required ? { required: true } : {}),
    };
    const slotProps = {
        ...(Object.keys(attributes).length > 0 ? { htmlInput: attributes } : {}),
        ...(start ? { input: { startAdornment: start } } : {}),
    };

    return {
        id: name,
        name,
        value: form.data[name],
        error: Boolean(error),
        helperText: error,
        fullWidth: true,
        ...(type ? { type } : {}),
        ...(Object.keys(slotProps).length > 0 ? { slotProps } : {}),
        onChange: (event) => form.setData(name, event.target.value),
    };
}
