/** Aplica a máscara (00) 0000-0000 ou (00) 00000-0000 ao que já foi digitado. */
export function formatPhone(value) {
    const digits = (value ?? '').replace(/\D/g, '').slice(0, 11);

    if (digits.length <= 2) {
        return digits && `(${digits}`;
    }

    const split = digits.length === 11 ? 7 : 6;
    const start = `(${digits.slice(0, 2)}) ${digits.slice(2, split)}`;

    return digits.length > split ? `${start}-${digits.slice(split)}` : start;
}
