import FormDialog from '@/Components/FormDialog';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { axe } from 'jest-axe';
import { useState } from 'react';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';

const dialog = (props = {}) =>
    render(
        <FormDialog
            open
            title="Editar usuário"
            onClose={() => {}}
            onSubmit={() => {}}
            {...props}
        >
            <label htmlFor="nome">Nome</label>
            <input id="nome" autoFocus />
        </FormDialog>,
    );

const pressEscape = (element) => fireEvent.keyDown(element, { key: 'Escape' });

describe('FormDialog', () => {
    beforeEach(() => setPageProps({ translations }));
    afterEach(() => vi.unstubAllGlobals());

    test('o título é o nome do diálogo e o foco fica dentro dele', async () => {
        dialog();

        const element = screen.getByRole('dialog', { name: 'Editar usuário' });

        await waitFor(() => expect(element.contains(document.activeElement)).toBe(true));
    });

    test('devolve o foco a quem abriu', async () => {
        function Page() {
            const [open, setOpen] = useState(false);

            return (
                <>
                    <button type="button" onClick={() => setOpen(true)}>
                        Abrir
                    </button>
                    {open && (
                        <FormDialog
                            open
                            title="Novo"
                            onClose={() => setOpen(false)}
                            onSubmit={() => {}}
                        />
                    )}
                </>
            );
        }

        render(<Page />);

        const opener = screen.getByRole('button', { name: 'Abrir' });

        opener.focus();
        fireEvent.click(opener);
        fireEvent.click(screen.getByRole('button', { name: 'Cancelar' }));

        await waitFor(() => expect(document.activeElement).toBe(opener));
    });

    test('Esc fecha quando não há alterações', () => {
        const onClose = vi.fn();

        dialog({ onClose });

        pressEscape(screen.getByRole('dialog'));

        expect(onClose).toHaveBeenCalledOnce();
    });

    test('com alterações, Esc pede confirmação e cancelar mantém o formulário', async () => {
        const onClose = vi.fn();

        dialog({ onClose, isDirty: true });

        pressEscape(screen.getByRole('dialog'));

        const confirm = await screen.findByRole('dialog', { name: 'Descartar alterações' });

        expect(onClose).not.toHaveBeenCalled();

        fireEvent.click(within(confirm, 'Cancelar'));

        await waitFor(() =>
            expect(screen.queryByRole('dialog', { name: 'Descartar alterações' })).toBeNull(),
        );
        expect(onClose).not.toHaveBeenCalled();
        expect(screen.getByLabelText('Nome')).toBeTruthy();
    });

    test('com alterações, Cancelar e descartar fecham o formulário', async () => {
        const onClose = vi.fn();

        dialog({ onClose, isDirty: true });

        fireEvent.click(screen.getByRole('button', { name: 'Cancelar' }));

        const confirm = await screen.findByRole('dialog', { name: 'Descartar alterações' });

        fireEvent.click(within(confirm, 'Descartar'));

        expect(onClose).toHaveBeenCalledOnce();
    });

    test('enviar chama onSubmit sem recarregar a página', () => {
        const onSubmit = vi.fn();

        dialog({ onSubmit });

        fireEvent.click(screen.getByRole('button', { name: 'Salvar' }));

        expect(onSubmit).toHaveBeenCalledOnce();
    });

    test('durante o processamento o botão fica desabilitado e Esc não fecha', () => {
        const onClose = vi.fn();

        dialog({ onClose, processing: true });

        expect(screen.getByRole('button', { name: 'Salvar' }).disabled).toBe(true);

        pressEscape(screen.getByRole('dialog'));

        expect(onClose).not.toHaveBeenCalled();
    });

    test('aceita outro rótulo no botão de envio', () => {
        dialog({ submitLabel: 'Criar' });

        expect(screen.getByRole('button', { name: 'Criar' })).toBeTruthy();
    });

    test('ocupa a tela inteira abaixo de 600 px', () => {
        vi.stubGlobal('matchMedia', (query) => ({
            matches: query.includes('max-width:599'),
            media: query,
            addEventListener: () => {},
            removeEventListener: () => {},
            addListener: () => {},
            removeListener: () => {},
        }));

        dialog();

        expect(document.querySelector('.MuiDialog-paperFullScreen')).not.toBeNull();
    });

    test('em telas maiores não ocupa a tela inteira', () => {
        dialog();

        expect(document.querySelector('.MuiDialog-paperFullScreen')).toBeNull();
    });

    test('não tem violações de acessibilidade', async () => {
        const { baseElement } = dialog();

        expect(await axe(baseElement)).toHaveNoViolations();
    });
});

function within(element, name) {
    return [...element.querySelectorAll('button')].find(
        (button) => button.textContent === name,
    );
}
