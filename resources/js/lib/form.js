/**
 * Propriedades de um `TextField` ligado a um formulário do Inertia.
 * O obrigatório vai ao campo nativo, sem asterisco no rótulo.
 */
export function fieldProps(
    form,
    name,
    { required = false, htmlInput = {}, adornment = null } = {},
) {
    const error = form.errors[name];
    const attributes = { ...htmlInput, ...(required ? { required: true } : {}) };
    const slotProps = {
        ...(Object.keys(attributes).length > 0 ? { htmlInput: attributes } : {}),
        ...(adornment ? { input: { startAdornment: adornment } } : {}),
    };

    return {
        id: name,
        name,
        value: form.data[name],
        error: Boolean(error),
        helperText: error,
        fullWidth: true,
        ...(Object.keys(slotProps).length > 0 ? { slotProps } : {}),
        onChange: (event) => form.setData(name, event.target.value),
    };
}
