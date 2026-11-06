import SelectField from '@/Components/SelectField';
import { useTranslations } from '@/lib/i18n';
import Stack from '@mui/material/Stack';
import TextField from '@mui/material/TextField';
import Typography from '@mui/material/Typography';

/** Valores iniciais: segredo começa vazio, e vazio mantém o atual. */
export function environmentValues(groups) {
    return Object.fromEntries(
        groups
            .flatMap(({ parameters }) => parameters)
            .filter(({ locked }) => !locked)
            .map(({ key, value }) => [key, value ?? '']),
    );
}

/** Leva o foco ao primeiro campo recusado, que pode estar longe do botão. */
export function focusFirstError(errors) {
    const field = Object.keys(errors).find((name) => name.startsWith('values.'));

    if (field) {
        document.getElementById(`env_${field.slice(7)}`)?.focus();
    }
}

function Parameter({ parameter, form, readOnly }) {
    const __ = useTranslations();
    const { key, description, type, secret, locked, overridden, effective } = parameter;

    const id = `env_${key}`;
    const error = form.errors[`values.${key}`];
    const value = form.data.values[key] ?? '';

    const notes = [
        description,
        ...(locked ? [__('Managed by the system.')] : []),
        ...(secret && !locked
            ? [
                  parameter.has_value
                      ? __('Value hidden. Leave blank to keep it.')
                      : __('Not defined. Fill in to set it.'),
              ]
            : []),
        ...(overridden
            ? [__('Defined by the server, which takes priority over the file.')]
            : []),
        ...(effective !== null && effective !== undefined
            ? [__('Value in effect: :value', { value: effective })]
            : []),
    ].join(' ');

    const common = {
        id,
        name: key,
        label: key,
        fullWidth: true,
        value,
        disabled: readOnly || locked,
        error: Boolean(error),
        helperText: error ? `${error} ${notes}` : notes,
        onChange: (event) =>
            form.setData('values', {
                ...form.data.values,
                [key]: event.target.value,
            }),
    };

    if (type === 'boolean' && !secret) {
        return (
            <SelectField {...common} size="medium">
                <option value="true">{__('Yes')}</option>
                <option value="false">{__('No')}</option>
            </SelectField>
        );
    }

    return (
        <TextField
            {...common}
            type={secret ? 'password' : 'text'}
            autoComplete={secret ? 'new-password' : 'off'}
        />
    );
}

/**
 * Parâmetros do ambiente por grupo, usados no assistente e no painel. O `form`
 * é um `useForm` com `values`.
 */
export default function EnvironmentForm({ groups, form, readOnly = false }) {
    return (
        <Stack spacing={4}>
            {groups.map((group) => (
                <Stack key={group.key} component="section" spacing={2}>
                    <Typography component="h2" variant="h6">
                        {group.label}
                    </Typography>

                    {group.parameters.map((parameter) => (
                        <Parameter
                            key={parameter.key}
                            parameter={parameter}
                            form={form}
                            readOnly={readOnly}
                        />
                    ))}
                </Stack>
            ))}
        </Stack>
    );
}
