/** Aplica a máscara 000.000.000-00 ao que já foi digitado. */
export function formatCpf(value) {
    return (value ?? '')
        .replace(/\D/g, '')
        .slice(0, 11)
        .replace(/^(\d{3})(\d)/, '$1.$2')
        .replace(/^(\d{3}\.\d{3})(\d)/, '$1.$2')
        .replace(/^(\d{3}\.\d{3}\.\d{3})(\d)/, '$1-$2');
}

/** Mesma regra de `App\Rules\ValidCpf`: aceita o valor com ou sem máscara. */
export function isValidCpf(value) {
    const digits = (value ?? '').replace(/\D/g, '');

    if (!/^\d{11}$/.test(digits) || /^(\d)\1{10}$/.test(digits)) {
        return false;
    }

    return [9, 10].every((position) => {
        const sum = [...digits.slice(0, position)].reduce(
            (total, digit, index) =>
                total + Number(digit) * (position + 1 - index),
            0,
        );

        return Number(digits[position]) === ((sum * 10) % 11) % 10;
    });
}
