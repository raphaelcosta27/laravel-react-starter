export const BRAZILIAN_STATES = [
    ['AC', 'Acre'],
    ['AL', 'Alagoas'],
    ['AP', 'Amapá'],
    ['AM', 'Amazonas'],
    ['BA', 'Bahia'],
    ['CE', 'Ceará'],
    ['DF', 'Distrito Federal'],
    ['ES', 'Espírito Santo'],
    ['GO', 'Goiás'],
    ['MA', 'Maranhão'],
    ['MT', 'Mato Grosso'],
    ['MS', 'Mato Grosso do Sul'],
    ['MG', 'Minas Gerais'],
    ['PA', 'Pará'],
    ['PB', 'Paraíba'],
    ['PR', 'Paraná'],
    ['PE', 'Pernambuco'],
    ['PI', 'Piauí'],
    ['RJ', 'Rio de Janeiro'],
    ['RN', 'Rio Grande do Norte'],
    ['RS', 'Rio Grande do Sul'],
    ['RO', 'Rondônia'],
    ['RR', 'Roraima'],
    ['SC', 'Santa Catarina'],
    ['SP', 'São Paulo'],
    ['SE', 'Sergipe'],
    ['TO', 'Tocantins'],
];

export const NO_NUMBER = 'S/N';

export function isBrazil(country) {
    return ['brasil', 'brazil', 'br'].includes(
        (country ?? '').trim().toLowerCase(),
    );
}

/** Aplica a máscara 00000-000 ao que já foi digitado. */
export function formatPostalCode(value) {
    return (value ?? '')
        .replace(/\D/g, '')
        .slice(0, 8)
        .replace(/^(\d{5})(\d)/, '$1-$2');
}

/** Linha única para listas. */
export function addressSummary(address) {
    const postalCode = isBrazil(address.country)
        ? formatPostalCode(address.postal_code)
        : address.postal_code;

    return `${address.street}, ${address.number} - ${address.neighborhood}, ${address.city}/${address.state} - ${postalCode}`;
}
