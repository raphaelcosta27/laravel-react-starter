import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

/**
 * Fornecedor dos pacotes Composer que trazem páginas. O Vite só aceita texto
 * literal em `import.meta.glob`, então, ao trocar o fornecedor, troque também o
 * caminho em PACKAGE_PAGES, logo abaixo. Veja docs/guias/estender-template.md.
 */
export const PACKAGE_VENDOR = 'raphaelcosta27';

const PAGES = import.meta.glob('./Pages/**/*.jsx');

const PACKAGE_PAGES = import.meta.glob(
    '../../vendor/raphaelcosta27/*/resources/js/Pages/**/*.jsx',
);

const SEPARATOR = '::';

/** Estas telas existem sem sessão, ou antes de a conta estar liberada. */
const OUTSIDE_SHELL = /^(Auth\/|Install\/|Welcome$|Error$)/;

function split(name) {
    const at = name.indexOf(SEPARATOR);

    return at === -1
        ? { pkg: null, path: name }
        : { pkg: name.slice(0, at), path: name.slice(at + SEPARATOR.length) };
}

/** Chave do arquivo no glob: `pacote::Caminho` fica em vendor/<fornecedor>/<pacote>. */
export function pageKey(name) {
    const { pkg, path } = split(name);

    return pkg === null
        ? `./Pages/${path}.jsx`
        : `../../vendor/${PACKAGE_VENDOR}/${pkg}/resources/js/Pages/${path}.jsx`;
}

/** A regra vale para o caminho, sem o nome do pacote. */
export function isOutsideShell(name) {
    return OUTSIDE_SHELL.test(split(name).path);
}

/**
 * Carrega a página pelo nome do Inertia e aplica o layout às telas de dentro do
 * sistema. Os globs podem ser trocados nos testes.
 */
export async function resolvePage(
    name,
    { globs = { pages: PAGES, packagePages: PACKAGE_PAGES }, layout } = {},
) {
    const pages = split(name).pkg === null ? globs.pages : globs.packagePages;
    const page = await resolvePageComponent(pageKey(name), pages);

    if (layout && !isOutsideShell(name)) {
        page.default.layout = layout;
    }

    return page;
}
