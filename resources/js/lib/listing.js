import { router } from '@inertiajs/react';

/** Abre ou fecha um formulário sem recarregar a tabela. */
export const FORM_VISIT = {
    only: ['form'],
    preserveState: true,
    preserveScroll: true,
};

/** Envia um formulário mantendo a listagem como está. */
export const FORM_SUBMIT = { preserveState: true, preserveScroll: true };

/** Leva filtros, ordem e página atuais para o endereço informado. */
export const withListingQuery = (url) => `${url}${window.location.search}`;

export const closeForm = (url) =>
    router.visit(withListingQuery(url), FORM_VISIT);
