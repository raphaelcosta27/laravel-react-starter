import AddressFields from '@/Components/AddressFields';
import { addressSummary, formatPostalCode, isBrazil } from '@/lib/address';
import AddressesSection from '@/Pages/Profile/Partials/AddressesSection';
import AddressFormDialog from '@/Pages/Profile/Partials/AddressFormDialog';
import { useForm } from '@inertiajs/react';
import {
    act,
    fireEvent,
    render,
    screen,
    waitFor,
    within,
} from '@testing-library/react';
import { axe } from 'jest-axe';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { formActions, router } from './helpers/inertia';

const found = {
    street: 'Avenida Paulista',
    neighborhood: 'Bela Vista',
    city: 'São Paulo',
    state: 'SP',
};

const paulista = {
    id: 1,
    postal_code: '01310100',
    street: 'Avenida Paulista',
    number: '1000',
    complement: null,
    reference: null,
    neighborhood: 'Bela Vista',
    city: 'São Paulo',
    state: 'SP',
    country: 'Brasil',
    is_main: true,
    is_billing: true,
    is_delivery: true,
    recipient_name: 'João',
};

const second = {
    ...paulista,
    id: 2,
    street: 'Rua Augusta',
    is_main: false,
    is_billing: false,
    is_delivery: false,
    recipient_name: null,
};

const reply = (status, body = {}) => ({
    status,
    ok: status < 400,
    statusText: String(status),
    json: async () => body,
});

const empty = {
    postal_code: '',
    street: '',
    number: '',
    complement: '',
    reference: '',
    neighborhood: '',
    city: '',
    state: '',
    country: 'Brasil',
};

/** Segundo formulário, sem nada do cadastro de usuário. */
function OtherForm({ initial = {} }) {
    const form = useForm({ ...empty, ...initial });

    return (
        <form>
            <AddressFields form={form} />
        </form>
    );
}

const type = (label, value) =>
    fireEvent.change(screen.getByLabelText(label), { target: { value } });

const status = () => screen.getByRole('status').textContent;

beforeEach(() => {
    vi.stubGlobal('fetch', vi.fn(async () => reply(200, found)));
});

afterEach(() => vi.unstubAllGlobals());

describe('auxiliares', () => {
    test.each([
        ['', ''],
        [null, ''],
        ['01310', '01310'],
        ['013101', '01310-1'],
        ['01310100', '01310-100'],
        ['013101009', '01310-100'],
    ])('máscara de "%s"', (value, expected) => {
        expect(formatPostalCode(value)).toBe(expected);
    });

    test.each([
        ['Brasil', true],
        [' brazil ', true],
        ['BR', true],
        ['Portugal', false],
        ['', false],
    ])('país "%s"', (country, expected) => {
        expect(isBrazil(country)).toBe(expected);
    });

    test('resumo do endereço', () => {
        expect(addressSummary(paulista)).toBe(
            'Avenida Paulista, 1000 - Bela Vista, São Paulo/SP - 01310-100',
        );
    });
});

describe('AddressFields em outro formulário', () => {
    test('preenche pelo CEP, avisa e leva o foco ao número', async () => {
        render(<OtherForm />);

        const postalCode = screen.getByLabelText('Postal code');

        postalCode.focus();
        type('Postal code', '01310100');

        expect(postalCode.value).toBe('01310-100');
        expect(status()).toBe('Searching postal code...');

        await waitFor(() =>
            expect(screen.getByLabelText('Street').value).toBe('Avenida Paulista'),
        );

        expect(screen.getByLabelText('Neighborhood').value).toBe('Bela Vista');
        expect(screen.getByLabelText('City').value).toBe('São Paulo');
        expect(screen.getByLabelText('State').value).toBe('SP');
        expect(status()).toBe(
            'Address filled in from the postal code. Review the fields.',
        );
        expect(document.activeElement).toBe(screen.getByLabelText('Number'));
        expect(fetch.mock.calls[0][0]).toBe('/postal-code.show');
    });

    test('não busca com o CEP incompleto', () => {
        render(<OtherForm />);

        type('Postal code', '0131010');

        expect(fetch).not.toHaveBeenCalled();
    });

    test('CEP inexistente avisa e deixa preencher', async () => {
        fetch.mockResolvedValueOnce(reply(404));
        render(<OtherForm />);

        type('Postal code', '99999999');

        await waitFor(() =>
            expect(status()).toBe(
                'Postal code not found. Fill in the address manually.',
            ),
        );

        type('Street', 'Rua Manual');

        expect(screen.getByLabelText('Street').value).toBe('Rua Manual');
    });

    test.each([
        ['serviço fora do ar', () => fetch.mockResolvedValueOnce(reply(503))],
        ['sem rede', () => fetch.mockRejectedValueOnce(new TypeError('rede'))],
    ])('%s avisa e não bloqueia', async (_name, prepare) => {
        prepare();
        render(<OtherForm />);

        type('Postal code', '01310100');

        await waitFor(() =>
            expect(status()).toBe(
                'The postal code search is unavailable. Fill in the address manually.',
            ),
        );

        expect(screen.getByLabelText('Street').disabled).toBe(false);
    });

    test('não sobrescreve o que foi digitado durante a busca', async () => {
        let finish;

        fetch.mockReturnValueOnce(
            new Promise((resolve) => {
                finish = resolve;
            }),
        );
        render(<OtherForm />);

        type('Postal code', '01310100');
        type('Street', 'Rua da Pessoa');

        await act(async () => finish(reply(200, found)));

        expect(screen.getByLabelText('Street').value).toBe('Rua da Pessoa');
        expect(screen.getByLabelText('City').value).toBe('São Paulo');
    });

    test('não tira o foco de quem já seguiu adiante', async () => {
        render(<OtherForm />);

        const postalCode = screen.getByLabelText('Postal code');

        postalCode.focus();
        type('Postal code', '01310100');
        screen.getByLabelText('Complement').focus();

        await waitFor(() =>
            expect(screen.getByLabelText('City').value).toBe('São Paulo'),
        );

        expect(document.activeElement).toBe(screen.getByLabelText('Complement'));
    });

    test('uma busca antiga não substitui a mais nova', async () => {
        const finish = [];

        fetch.mockImplementation(
            (_url, { signal }) =>
                new Promise((resolve, reject) => {
                    finish.push(resolve);
                    signal.addEventListener('abort', () =>
                        reject(new DOMException('cancelada', 'AbortError')),
                    );
                }),
        );
        render(<OtherForm />);

        type('Postal code', '01310100');
        type('Postal code', '0131010');
        type('Postal code', '01310200');

        await act(async () => finish[1](reply(200, { ...found, city: 'Nova' })));
        await act(async () => finish[0](reply(200, { ...found, city: 'Velha' })));

        expect(screen.getByLabelText('City').value).toBe('Nova');
    });

    test('CEP genérico preenche o que veio', async () => {
        fetch.mockResolvedValueOnce(
            reply(200, { ...found, street: '', neighborhood: '' }),
        );
        render(<OtherForm initial={{ street: 'Rua Antiga' }} />);

        type('Postal code', '13560970');

        await waitFor(() =>
            expect(screen.getByLabelText('City').value).toBe('São Paulo'),
        );

        expect(screen.getByLabelText('Street').value).toBe('Rua Antiga');
    });

    test('outro país desliga busca, máscara e lista de estados', () => {
        render(<OtherForm />);

        type('Country', 'United Kingdom');
        type('Postal code', 'SW1A 1AA');
        type('State', 'England');

        expect(screen.getByLabelText('Postal code').value).toBe('SW1A 1AA');
        expect(screen.getByLabelText('State').tagName).toBe('INPUT');
        expect(fetch).not.toHaveBeenCalled();
    });

    test('a lista traz as 27 unidades federativas', () => {
        render(<OtherForm />);

        expect(screen.getByLabelText('State').options).toHaveLength(28);
    });

    test('S/N preenche o número e pode ser desfeito', () => {
        render(<OtherForm />);

        fireEvent.click(screen.getByLabelText('No number'));

        expect(screen.getByLabelText('Number').value).toBe('S/N');
        expect(screen.getByLabelText('Number').disabled).toBe(true);

        fireEvent.click(screen.getByLabelText('No number'));

        expect(screen.getByLabelText('Number').value).toBe('');
    });

    test('marca os obrigatórios e os opcionais', () => {
        render(<OtherForm />);

        ['Postal code', 'Street', 'Number', 'Neighborhood', 'City', 'State', 'Country'].forEach(
            (label) => expect(screen.getByLabelText(label).required).toBe(true),
        );
        ['Complement', 'Reference point'].forEach((label) =>
            expect(screen.getByLabelText(label).required).toBe(false),
        );
    });

    test('não tem violações', async () => {
        const { container } = render(<OtherForm />);

        expect(await axe(container)).toHaveNoViolations();
    });
});

describe('AddressFormDialog', () => {
    test('o primeiro endereço já vem como principal', () => {
        render(<AddressFormDialog first onClose={() => {}} />);

        const main = screen.getByLabelText('Main address');

        expect(main.checked).toBe(true);
        expect(main.disabled).toBe(true);
    });

    test('o país vem do padrão configurado', () => {
        render(<AddressFormDialog defaultCountry="Portugal" onClose={() => {}} />);

        expect(screen.getByLabelText('Country').value).toBe('Portugal');
    });

    test('o principal atual não pode ser desmarcado', () => {
        render(<AddressFormDialog record={paulista} onClose={() => {}} />);

        expect(screen.getByLabelText('Main address').disabled).toBe(true);
        expect(
            screen.getByText('To change the main address, mark another one as main.'),
        ).toBeTruthy();
    });

    test('entrega pede o dono do endereço', () => {
        render(<AddressFormDialog onClose={() => {}} />);

        expect(
            screen.queryByLabelText('Address owner (who will receive)'),
        ).toBeNull();

        fireEvent.click(screen.getByLabelText('Delivery address'));

        expect(
            screen.getByLabelText('Address owner (who will receive)').required,
        ).toBe(true);
    });

    test('salva mantendo o que foi digitado nos outros formulários', () => {
        const onClose = vi.fn();

        render(<AddressFormDialog onClose={onClose} />);

        fireEvent.submit(screen.getByRole('dialog').querySelector('form'));

        const [url, options] = formActions.post.mock.calls[0];

        expect(url).toBe('/profile.addresses.store');
        expect(options.preserveState).toBe(true);

        options.onSuccess();

        expect(onClose).toHaveBeenCalled();
    });

    test('edita o endereço com o CEP mascarado', () => {
        render(<AddressFormDialog record={paulista} onClose={() => {}} />);

        expect(screen.getByLabelText('Postal code').value).toBe('01310-100');

        fireEvent.submit(screen.getByRole('dialog').querySelector('form'));

        expect(formActions.patch.mock.calls[0][0]).toBe('/profile.addresses.update');
    });

    test('não tem violações', async () => {
        const { baseElement } = render(
            <AddressFormDialog record={paulista} onClose={() => {}} />,
        );

        expect(await axe(baseElement)).toHaveNoViolations();
    });
});

describe('AddressesSection', () => {
    const openDeletion = (index = 0) =>
        fireEvent.click(
            screen.getAllByRole('button', { name: 'Delete address' })[index],
        );

    test('a lista vazia orienta e oferece adicionar', () => {
        render(<AddressesSection addresses={[]} />);

        expect(
            screen.getByText('You have no addresses yet. Add the first one.'),
        ).toBeTruthy();

        fireEvent.click(screen.getByRole('button', { name: 'Add address' }));

        expect(screen.getByLabelText('Main address').checked).toBe(true);
    });

    test('mostra o resumo, as marcas em texto e quem recebe', () => {
        render(<AddressesSection addresses={[paulista]} />);

        expect(screen.getByText('Main · Billing · Delivery')).toBeTruthy();
        expect(screen.getByText('Receives: João')).toBeTruthy();
    });

    test('exclui um endereço comum com confirmação simples', () => {
        render(<AddressesSection addresses={[paulista, second]} />);

        openDeletion(1);

        fireEvent.click(
            within(screen.getByRole('dialog')).getByRole('button', { name: 'Delete' }),
        );

        expect(router.delete.mock.calls[0][1].data).toEqual({});
    });

    test('excluir o principal pede o próximo', () => {
        render(<AddressesSection addresses={[paulista, second]} />);

        openDeletion(0);

        const dialog = within(screen.getByRole('dialog'));
        const confirm = dialog.getByRole('button', { name: 'Delete' });

        expect(confirm.disabled).toBe(true);

        fireEvent.change(dialog.getByLabelText('New main address'), {
            target: { value: '2' },
        });
        fireEvent.click(confirm);

        expect(router.delete.mock.calls[0][1].data).toEqual({ new_main_id: '2' });
    });

    test('o único endereço é excluído sem escolha', () => {
        render(<AddressesSection addresses={[paulista]} />);

        openDeletion(0);

        expect(screen.queryByLabelText('New main address')).toBeNull();
    });

    test('devolve o foco ao botão de adicionar depois de excluir', async () => {
        const { rerender } = render(<AddressesSection addresses={[second]} />);

        router.delete.mockImplementationOnce((_url, options) => {
            rerender(<AddressesSection addresses={[]} />);
            options.onSuccess();
            options.onFinish();
        });

        openDeletion(0);
        fireEvent.click(
            within(screen.getByRole('dialog')).getByRole('button', { name: 'Delete' }),
        );

        await waitFor(() => expect(screen.queryByRole('dialog')).toBeNull());

        expect(document.activeElement).toBe(
            screen.getByRole('button', { name: 'Add address' }),
        );
    });

    test('não tem violações', async () => {
        const { container } = render(
            <AddressesSection addresses={[paulista, second]} />,
        );

        expect(await axe(container)).toHaveNoViolations();
    });
});
