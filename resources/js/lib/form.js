/**
 * Propriedades de um `TextField` ligado a um formulário do Inertia.
 * O obrigatório vai ao campo nativo, sem asterisco no rótulo.
 */
export function fieldProps(form, name, { required = false } = {}) {
    const error = form.errors[name];

    return {
        id: name,
        name,
        value: form.data[name],
        error: Boolean(error),
        helperText: error,
        fullWidth: true,
        ...(required ? { slotProps: { htmlInput: { required: true } } } : {}),
        onChange: (event) => form.setData(name, event.target.value),
    };
}
