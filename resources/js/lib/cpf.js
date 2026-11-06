/** Aplica a máscara 000.000.000-00 ao que já foi digitado. */
export function formatCpf(value) {
    return (value ?? '')
        .replace(/\D/g, '')
        .slice(0, 11)
        .replace(/^(\d{3})(\d)/, '$1.$2')
        .replace(/^(\d{3}\.\d{3})(\d)/, '$1.$2')
        .replace(/^(\d{3}\.\d{3}\.\d{3})(\d)/, '$1-$2');
}
