import { formatPhone } from '@/lib/phone';
import ContactFormDialog from '@/Pages/Profile/Partials/ContactFormDialog';
import ContactsSection from '@/Pages/Profile/Partials/ContactsSection';
import {
    fireEvent,
    render,
    screen,
    waitFor,
    within,
} from '@testing-library/react';
import { axe } from 'jest-axe';
import { describe, expect, test, vi } from 'vitest';
import { formActions, router, setPageProps } from './helpers/inertia';

const relationships = [
    { value: 'Mother', label: 'Mãe' },
    { value: 'Friend', label: 'Amigo(a)' },
];

const mobile = {
    id: 1,
    type: 'phone',
    value: '11987654321',
    is_whatsapp: true,
    is_emergency: false,
    relationship: null,
    relationship_label: null,
    contact_name: null,
};

const emergency = {
    id: 2,
    type: 'email',
    value: 'maria@example.com',
    is_whatsapp: false,
    is_emergency: true,
    relationship: 'Mother',
    relationship_label: 'Mãe',
    contact_name: 'Maria Lima',
};

const type = (label, value) =>
    fireEvent.change(screen.getByLabelText(label), { target: { value } });

const dialog = (props = {}) =>
    render(
        <ContactFormDialog
            relationships={relationships}
            onClose={() => {}}
            {...props}
        />,
    );

describe('formatPhone', () => {
    test.each([
        ['', ''],
        [null, ''],
        ['1', '(1'],
        ['11', '(11'],
        ['119', '(11) 9'],
        ['119876', '(11) 9876'],
        ['1198765', '(11) 9876-5'],
        ['1134567890', '(11) 3456-7890'],
        ['11987654321', '(11) 98765-4321'],
        ['119876543210000', '(11) 98765-4321'],
        ['(11) 98765-4321', '(11) 98765-4321'],
    ])('de "%s"', (value, expected) => {
        expect(formatPhone(value)).toBe(expected);
    });
});

describe('ContactsSection', () => {
    test('a lista vazia orienta e oferece adicionar', () => {
        render(<ContactsSection contacts={[]} relationships={relationships} />);

        expect(
            screen.getByText('You have no contacts yet. Add a phone or an email.'),
        ).toBeTruthy();

        fireEvent.click(screen.getByRole('button', { name: 'Add contact' }));

        expect(screen.getByRole('dialog', { name: 'Add contact' })).toBeTruthy();
    });

    test('mostra o telefone com máscara e o WhatsApp em texto', () => {
        render(
            <ContactsSection contacts={[mobile]} relationships={relationships} />,
        );

        const row = screen.getByRole('listitem');

        expect(within(row).getByText('(11) 98765-4321')).toBeTruthy();
        expect(within(row).getByText('Phone · WhatsApp')).toBeTruthy();
    });

    test('identifica o contato de emergência com nome e parentesco', () => {
        render(
            <ContactsSection contacts={[emergency]} relationships={relationships} />,
        );

        expect(screen.getByText('Emergency: Maria Lima (Mãe)')).toBeTruthy();
    });

    test('as ações têm ícone, nome do contato e descrição', () => {
        render(
            <ContactsSection contacts={[mobile]} relationships={relationships} />,
        );

        const group = screen.getByRole('group', { name: '(11) 98765-4321' });

        within(group)
            .getAllByRole('button')
            .forEach((button) => {
                expect(button.querySelector('svg')).toBeTruthy();
                expect(
                    document.getElementById(button.getAttribute('aria-describedby'))
                        ?.textContent,
                ).toBeTruthy();
            });
    });

    test('edita pelo modal com os dados do contato', () => {
        render(
            <ContactsSection contacts={[mobile]} relationships={relationships} />,
        );

        fireEvent.click(screen.getByRole('button', { name: 'Edit contact' }));

        expect(screen.getByRole('dialog', { name: 'Edit contact' })).toBeTruthy();
        expect(screen.getByLabelText('Phone').value).toBe('(11) 98765-4321');
        expect(screen.getByLabelText('This number has WhatsApp').checked).toBe(true);
    });

    test('exclui somente depois de confirmar', () => {
        render(
            <ContactsSection contacts={[mobile]} relationships={relationships} />,
        );

        fireEvent.click(screen.getByRole('button', { name: 'Delete contact' }));

        expect(router.delete).not.toHaveBeenCalled();

        const confirmation = screen.getByRole('dialog', { name: 'Delete contact' });

        fireEvent.click(within(confirmation).getByRole('button', { name: 'Delete' }));

        expect(router.delete).toHaveBeenCalledTimes(1);
        expect(router.delete.mock.calls[0][0]).toBe('/profile.contacts.destroy');
        expect(router.delete.mock.calls[0][1].preserveState).toBe(true);
    });

    test('devolve o foco ao botão de adicionar depois de excluir', async () => {
        const { rerender } = render(
            <ContactsSection contacts={[mobile]} relationships={relationships} />,
        );

        router.delete.mockImplementationOnce((_url, options) => {
            rerender(
                <ContactsSection contacts={[]} relationships={relationships} />,
            );
            options.onFinish();
        });

        fireEvent.click(screen.getByRole('button', { name: 'Delete contact' }));
        fireEvent.click(
            within(screen.getByRole('dialog', { name: 'Delete contact' })).getByRole(
                'button',
                { name: 'Delete' },
            ),
        );

        await waitFor(() => expect(screen.queryByRole('dialog')).toBeNull());

        expect(document.activeElement).toBe(
            screen.getByRole('button', { name: 'Add contact' }),
        );
    });

    test('não tem violações', async () => {
        const { container } = render(
            <ContactsSection
                contacts={[mobile, emergency]}
                relationships={relationships}
            />,
        );

        expect(await axe(container)).toHaveNoViolations();
    });
});

describe('ContactFormDialog', () => {
    test('aplica a máscara ao telefone', () => {
        dialog();

        type('Phone', '11987654321');

        expect(screen.getByLabelText('Phone').value).toBe('(11) 98765-4321');
    });

    test('não aplica a máscara ao e-mail', () => {
        dialog();

        type('Contact type', 'email');
        type('Email', 'ana@example.com');

        expect(screen.getByLabelText('Email').value).toBe('ana@example.com');
    });

    test('mudar o tipo esconde e desmarca o WhatsApp e limpa o valor', () => {
        dialog();

        fireEvent.click(screen.getByLabelText('This number has WhatsApp'));
        type('Phone', '11987654321');
        type('Contact type', 'email');

        expect(screen.queryByLabelText('This number has WhatsApp')).toBeNull();
        expect(screen.getByLabelText('Email').value).toBe('');

        type('Contact type', 'phone');

        expect(screen.getByLabelText('This number has WhatsApp').checked).toBe(false);
    });

    test('emergência pede parentesco e nome, ambos obrigatórios', () => {
        dialog();

        expect(screen.queryByLabelText('Relationship')).toBeNull();
        expect(screen.queryByLabelText('Contact name')).toBeNull();

        fireEvent.click(screen.getByLabelText('Emergency contact'));

        expect(screen.getByLabelText('Relationship').required).toBe(true);
        expect(screen.getByLabelText('Contact name').required).toBe(true);
        expect(
            within(screen.getByLabelText('Relationship')).getByRole('option', {
                name: 'Mãe',
            }),
        ).toBeTruthy();
    });

    test('a emergência também vale para e-mail', () => {
        dialog();

        type('Contact type', 'email');
        fireEvent.click(screen.getByLabelText('Emergency contact'));

        expect(screen.getByLabelText('Relationship')).toBeTruthy();
    });

    test('um parentesco que saiu da lista volta vazio', () => {
        dialog({ record: { ...emergency, relationship: 'Neighbor' } });

        expect(screen.getByLabelText('Relationship').value).toBe('');
    });

    test('cria mantendo o que foi digitado nos outros formulários', () => {
        const onClose = vi.fn();

        dialog({ onClose });

        fireEvent.submit(screen.getByRole('dialog').querySelector('form'));

        expect(formActions.post).toHaveBeenCalledTimes(1);

        const [url, options] = formActions.post.mock.calls[0];

        expect(url).toBe('/profile.contacts.store');
        expect(options.preserveState).toBe(true);

        options.onSuccess();

        expect(onClose).toHaveBeenCalled();
    });

    test('edita o contato escolhido', () => {
        dialog({ record: mobile });

        fireEvent.submit(screen.getByRole('dialog').querySelector('form'));

        expect(formActions.patch.mock.calls[0][0]).toBe('/profile.contacts.update');
    });

    test('mostra os erros por campo', () => {
        setPageProps({ errors: { value: 'Informe um telefone válido com DDD.' } });
        dialog();

        expect(
            screen.getByText('Informe um telefone válido com DDD.'),
        ).toBeTruthy();
    });

    test('não tem violações', async () => {
        const { baseElement } = dialog({ record: emergency });

        expect(await axe(baseElement)).toHaveNoViolations();
    });
});
