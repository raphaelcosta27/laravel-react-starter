/**
 * Propriedades de um `TextField` ligado a um formulário do Inertia.
 * O obrigatório vai ao campo nativo, sem asterisco no rótulo.
 */
export function fieldProps(form, name, { required = false, htmlInput = {} } = {}) {
    const error = form.errors[name];
    const attributes = { ...htmlInput, ...(required ? { required: true } : {}) };

    return {
        id: name,
        name,
        value: form.data[name],
        error: Boolean(error),
        helperText: error,
        fullWidth: true,
        ...(Object.keys(attributes).length > 0
            ? { slotProps: { htmlInput: attributes } }
            : {}),
        onChange: (event) => form.setData(name, event.target.value),
    };
}
