import { registerNetworkErrorHandler } from '@/lib/network-error';
import { afterEach, expect, test, vi } from 'vitest';

afterEach(() => {
    document.body.innerHTML = '';
    delete document.documentElement.dataset.networkError;
});

function fakeRouter() {
    const handlers = {};

    return {
        handlers,
        on: (name, handler) => {
            handlers[name] = handler;
        },
    };
}

test('exibe aviso em falha de rede', () => {
    document.documentElement.dataset.networkError = 'Sem conexão com o servidor.';
    const router = fakeRouter();
    const event = { preventDefault: vi.fn() };

    registerNetworkErrorHandler(router);
    router.handlers.exception(event);

    expect(event.preventDefault).toHaveBeenCalled();
    expect(document.querySelector('[role="alert"]').textContent).toBe(
        'Sem conexão com o servidor.',
    );
});

test('não repete o aviso', () => {
    const router = fakeRouter();

    registerNetworkErrorHandler(router);
    router.handlers.exception({ preventDefault: () => {} });
    router.handlers.exception({ preventDefault: () => {} });

    expect(document.querySelectorAll('[role="alert"]').length).toBe(1);
});
