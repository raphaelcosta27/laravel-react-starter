const token = () => {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
};

/**
 * `fetch` para rotas JSON da aplicação. Sessão vencida leva ao login; qualquer
 * outra falha rejeita, e quem chama decide o que dizer.
 */
export async function requestJson(url, { method = 'GET', body } = {}) {
    const response = await fetch(url, {
        method,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': token(),
            ...(body ? { 'Content-Type': 'application/json' } : {}),
        },
        body: body ? JSON.stringify(body) : undefined,
    });

    if ([401, 419].includes(response.status)) {
        window.location.assign(route('login'));
    }

    if (!response.ok) {
        throw new Error(String(response.status));
    }

    return response.json();
}
