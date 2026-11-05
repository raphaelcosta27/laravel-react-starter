import { forwardRef, useState } from 'react';
import { vi } from 'vitest';

const defaults = () => ({
    appName: 'Aplicação de Teste',
    branding: { logo: '/images/logo.svg' },
    auth: { user: null },
    translations: {},
});

let pageProps = defaults();

export function resetPageProps() {
    Object.values(router).forEach((method) => method.mockClear?.());
    pageProps = defaults();
}

export function setPageProps(props) {
    pageProps = { ...pageProps, ...props };
}

export function usePage() {
    return { props: pageProps, url: '/' };
}

export function useForm(initial = {}) {
    const [data, setValues] = useState(initial);
    const noop = () => {};

    return {
        data,
        setData: (key, value) =>
            setValues((current) => ({ ...current, [key]: value })),
        errors: pageProps.errors ?? {},
        processing: false,
        recentlySuccessful: false,
        post: noop,
        put: noop,
        patch: noop,
        delete: noop,
        reset: noop,
        clearErrors: noop,
    };
}

export function Head() {
    return null;
}

export const Link = forwardRef(function Link(
    { as, children, href, method, ...props },
    ref,
) {
    if (as === 'button') {
        return (
            <button ref={ref} type="button" {...props}>
                {children}
            </button>
        );
    }

    return (
        <a ref={ref} href={href} {...props}>
            {children}
        </a>
    );
});

export const router = {
    on: () => () => {},
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
    visit: vi.fn(),
};
