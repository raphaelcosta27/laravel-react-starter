import EditableCell from '@/Components/DataTable/EditableCell';
import { router } from '@inertiajs/react';
import { act, fireEvent, render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test, vi } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';

const cell = (props = {}) => {
    const announce = vi.fn();

    const view = render(
        <EditableCell
            value="Ana"
            field="name"
            url="/admin/users/1"
            label="Nome de Ana"
            announce={announce}
            {...props}
        />,
    );

    return { ...view, announce, input: screen.getByLabelText('Nome de Ana') };
};

const options = () => router.patch.mock.calls.at(-1)[2];

describe('EditableCell', () => {
    beforeEach(() => setPageProps({ translations }));

    test('salva ao sair do campo', () => {
        const { input } = cell();

        fireEvent.change(input, { target: { value: 'Ana Lima' } });
        fireEvent.blur(input);

        expect(router.patch).toHaveBeenCalledOnce();
        expect(router.patch.mock.calls[0][0]).toBe('/admin/users/1');
        expect(router.patch.mock.calls[0][1]).toEqual({ name: 'Ana Lima' });
    });

    test('salva com Enter sem enviar duas vezes ao sair depois', () => {
        const { input } = cell();

        fireEvent.change(input, { target: { value: 'Ana Lima' } });
        fireEvent.keyDown(input, { key: 'Enter' });
        fireEvent.blur(input);

        expect(router.patch).toHaveBeenCalledOnce();
    });

    test('Esc cancela e devolve o valor', () => {
        const { input } = cell();

        fireEvent.change(input, { target: { value: 'Ana Lima' } });
        fireEvent.keyDown(input, { key: 'Escape' });
        fireEvent.blur(input);

        expect(input.value).toBe('Ana');
        expect(router.patch).not.toHaveBeenCalled();
    });

    test('sem alteração nada é enviado', () => {
        const { input } = cell();

        fireEvent.blur(input);

        expect(router.patch).not.toHaveBeenCalled();
    });

    test('usa visita assíncrona que preserva estado e rolagem', () => {
        const { input } = cell();

        fireEvent.change(input, { target: { value: 'Novo' } });
        fireEvent.blur(input);

        expect(options()).toMatchObject({
            async: true,
            preserveState: true,
            preserveScroll: true,
        });
    });

    test('informa salvando e salvo, com anúncio', () => {
        const { input, announce } = cell();

        fireEvent.change(input, { target: { value: 'Novo' } });
        fireEvent.blur(input);

        expect(screen.getByText('Salvando...')).toBeTruthy();
        expect(announce).toHaveBeenLastCalledWith('Salvando...');

        act(() => {
            options().onSuccess();
            options().onFinish();
        });

        expect(screen.getByText('Salvo')).toBeTruthy();
        expect(announce).toHaveBeenLastCalledWith('Salvo');
    });

    test('erro de validação mantém o valor e é sinalizado', () => {
        const { input, announce } = cell();

        fireEvent.change(input, { target: { value: '' } });
        fireEvent.blur(input);

        act(() => {
            options().onError({ name: 'Campo obrigatório' });
            options().onFinish();
        });

        expect(screen.getByText('Campo obrigatório')).toBeTruthy();
        expect(input.value).toBe('');
        expect(input.getAttribute('aria-invalid')).toBe('true');
        expect(announce).toHaveBeenLastCalledWith('Campo obrigatório');
    });

    test('falha de rede não é apresentada como salva', () => {
        const { input } = cell();

        fireEvent.change(input, { target: { value: 'Novo' } });
        fireEvent.blur(input);

        act(() => options().onFinish());

        expect(screen.queryByText('Salvo')).toBeNull();
        expect(screen.getByText('Não foi possível salvar. Tente novamente.')).toBeTruthy();
        expect(input.value).toBe('Novo');
    });

    test('Esc depois de um erro volta ao último valor salvo', () => {
        const { input } = cell();

        fireEvent.change(input, { target: { value: '' } });
        fireEvent.blur(input);

        act(() => {
            options().onError({ name: 'Campo obrigatório' });
            options().onFinish();
        });

        fireEvent.keyDown(input, { key: 'Escape' });

        expect(input.value).toBe('Ana');
        expect(screen.queryByText('Campo obrigatório')).toBeNull();
    });

    test('o foco continua no campo seguinte depois do salvamento', () => {
        const row = (value) => (
            <table>
                <tbody>
                    <tr>
                        <td>
                            <EditableCell value={value} field="name" url="/a" label="Primeiro" announce={() => {}} />
                        </td>
                        <td>
                            <EditableCell value="Bia" field="name" url="/b" label="Segundo" announce={() => {}} />
                        </td>
                    </tr>
                </tbody>
            </table>
        );

        const { rerender } = render(row('Ana'));
        const first = screen.getByLabelText('Primeiro');
        const second = screen.getByLabelText('Segundo');

        fireEvent.change(first, { target: { value: 'Novo' } });
        fireEvent.blur(first);
        second.focus();

        act(() => {
            options().onSuccess();
            options().onFinish();
        });
        rerender(row('Novo'));

        expect(document.activeElement).toBe(second);
    });

    test('a resposta não sobrescreve uma edição em andamento', () => {
        const element = (value) => (
            <EditableCell value={value} field="name" url="/a" label="Nome de Ana" announce={() => {}} />
        );

        const { rerender } = render(element('Ana'));
        const input = screen.getByLabelText('Nome de Ana');

        fireEvent.change(input, { target: { value: 'Digitando' } });
        rerender(element('Valor do servidor'));

        expect(input.value).toBe('Digitando');
    });

    test('acompanha o valor do servidor quando não há edição', () => {
        const element = (value) => (
            <EditableCell value={value} field="name" url="/a" label="Nome de Ana" announce={() => {}} />
        );

        const { rerender } = render(element('Ana'));

        rerender(element('Outro'));

        expect(screen.getByLabelText('Nome de Ana').value).toBe('Outro');
    });

    test('não tem violações de acessibilidade', async () => {
        const { container } = cell();

        expect(await axe(container)).toHaveNoViolations();
    });
});
