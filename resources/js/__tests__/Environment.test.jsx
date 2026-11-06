import EnvironmentForm, {
    environmentValues,
    focusFirstError,
} from '@/Components/EnvironmentForm';
import EnvironmentPage from '@/Pages/Admin/Environment/Index';
import InstallPage from '@/Pages/Install/Index';
import { useForm } from '@inertiajs/react';
import { fireEvent, render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test } from 'vitest';
import { formActions, setPageProps } from './helpers/inertia';

const parameter = (key, data = {}) => ({
    key,
    description: `Descrição de ${key}`,
    type: 'text',
    secret: false,
    locked: false,
    value: 'valor',
    has_value: true,
    overridden: false,
    effective: null,
    ...data,
});

const groups = [
    {
        key: 'application',
        label: 'Aplicação',
        parameters: [
            parameter('APP_NAME', { value: 'Template' }),
            parameter('APP_DEBUG', { type: 'boolean', value: 'true' }),
            parameter('APP_KEY', { secret: true, locked: true, value: null }),
        ],
    },
    {
        key: 'database',
        label: 'Banco de dados',
        parameters: [
            parameter('DB_PASSWORD', { secret: true, value: null }),
            parameter('MAIL_PASSWORD', { secret: true, value: null, has_value: false }),
        ],
    },
];

function Form({ readOnly = false, list = groups }) {
    const form = useForm({ values: environmentValues(list) });

    return <EnvironmentForm groups={list} form={form} readOnly={readOnly} />;
}

beforeEach(() => {
    setPageProps({
        auth: {
            user: { id: 1, name: 'Ana', email: 'a@b.c' },
            displayName: 'Ana',
            permissions: [],
        },
    });
});

describe('EnvironmentForm', () => {
    test('agrupa com título e mostra a descrição', () => {
        render(<Form />);

        expect(screen.getByRole('heading', { name: 'Aplicação' })).toBeTruthy();
        expect(screen.getByRole('heading', { name: 'Banco de dados' })).toBeTruthy();
        expect(screen.getByLabelText('APP_NAME').value).toBe('Template');
        expect(screen.getByText('Descrição de APP_NAME')).toBeTruthy();
    });

    test('booleano vira sim ou não', () => {
        render(<Form />);

        expect(screen.getByLabelText('APP_DEBUG').tagName).toBe('SELECT');
    });

    test('segredo começa vazio, como senha, e explica o branco', () => {
        render(<Form />);

        const field = screen.getByLabelText('DB_PASSWORD');

        expect(field.type).toBe('password');
        expect(field.value).toBe('');
        expect(screen.getByText(/Value hidden. Leave blank to keep it./)).toBeTruthy();
        expect(screen.getByText(/Not defined. Fill in to set it./)).toBeTruthy();
    });

    test('a chave da aplicação fica bloqueada e fora do envio', () => {
        render(<Form />);

        expect(screen.getByLabelText('APP_KEY').disabled).toBe(true);
        expect(environmentValues(groups)).not.toHaveProperty('APP_KEY');
    });

    test('indica o valor definido pelo servidor', () => {
        render(
            <Form
                list={[
                    {
                        key: 'a',
                        label: 'A',
                        parameters: [
                            parameter('APP_URL', { overridden: true, effective: 'http://real' }),
                        ],
                    },
                ]}
            />,
        );

        expect(
            screen.getByText(/Defined by the server.*Value in effect: http:\/\/real/),
        ).toBeTruthy();
    });

    test('digitar altera só o campo', () => {
        render(<Form />);

        fireEvent.change(screen.getByLabelText('APP_NAME'), {
            target: { value: 'Novo' },
        });

        expect(screen.getByLabelText('APP_NAME').value).toBe('Novo');
        expect(screen.getByLabelText('APP_DEBUG').value).toBe('true');
    });

    test('mostra o erro do campo', () => {
        setPageProps({ errors: { 'values.APP_NAME': 'Valor recusado.' } });
        render(<Form />);

        expect(screen.getByText(/Valor recusado\./)).toBeTruthy();
    });

    test('leva o foco ao primeiro campo recusado', () => {
        render(<Form />);

        focusFirstError({ environment: 'x', 'values.APP_NAME': 'y' });

        expect(document.activeElement).toBe(screen.getByLabelText('APP_NAME'));
    });

    test('só leitura desliga os campos', () => {
        render(<Form readOnly />);

        expect(screen.getByLabelText('APP_NAME').disabled).toBe(true);
    });

    test('não tem violações', async () => {
        const { container } = render(<Form />);

        expect(await axe(container)).toHaveNoViolations();
    });
});

describe('tela do painel', () => {
    const page = (props = {}) =>
        render(
            <EnvironmentPage
                groups={groups}
                version="v"
                writable
                editable
                {...props}
            />,
        );

    test('salva pela rota do painel com a versão aberta', () => {
        page();

        expect(screen.getByRole('button', { name: 'Save' })).toBeTruthy();
    });

    test('sem salvamento fica só para leitura', () => {
        page({ editable: false });

        expect(
            screen.getByText('Saving is turned off. This page is read only.'),
        ).toBeTruthy();
        expect(screen.queryByRole('button', { name: 'Save' })).toBeNull();
    });

    test('sem permissão de escrita avisa', () => {
        page({ writable: false });

        expect(
            screen.getByText(
                'The environment file cannot be written. Review its permissions on the server.',
            ),
        ).toBeTruthy();
    });

    test('mostra a falha geral do arquivo', () => {
        setPageProps({ errors: { environment: 'O arquivo mudou.' } });
        page();

        expect(screen.getByText('O arquivo mudou.')).toBeTruthy();
    });

    test('avisa sobre reinício', () => {
        page();

        expect(screen.getByText(/may require restarting the server/)).toBeTruthy();
    });
});

describe('assistente', () => {
    test('começa pelo código e diz onde encontrá-lo', () => {
        render(<InstallPage step="unlock" codePath="storage/codigo.txt" />);

        expect(screen.getByText('Step 1 of 4')).toBeTruthy();
        expect(screen.getByRole('heading', { name: 'Installation code' })).toBeTruthy();
        expect(screen.getByText(/storage\/codigo\.txt/)).toBeTruthy();

        fireEvent.submit(screen.getByLabelText('Installation code').closest('form'));

        expect(formActions.post.mock.calls[0][0]).toBe('/install.unlock');
    });

    test('segundo passo mostra os parâmetros', () => {
        render(<InstallPage step="environment" groups={groups} />);

        expect(screen.getByText('Step 2 of 4')).toBeTruthy();
        expect(screen.getByLabelText('APP_NAME')).toBeTruthy();

        fireEvent.submit(screen.getByLabelText('APP_NAME').closest('form'));

        expect(formActions.post.mock.calls[0][0]).toBe('/install.environment');
    });

    test('terceiro passo prepara o banco e mostra a falha', () => {
        setPageProps({ errors: { database: 'Falhou.' } });
        render(<InstallPage step="database" />);

        expect(screen.getByText('Falhou.')).toBeTruthy();

        fireEvent.click(screen.getByRole('button', { name: 'Prepare the database' }));
    });

    test('último passo cria o usuário com a barra de força', () => {
        render(<InstallPage step="user" />);

        expect(screen.getByText('Step 4 of 4')).toBeTruthy();
        expect(screen.getByLabelText('Password').type).toBe('password');
        expect(screen.getByLabelText('Confirm Password')).toBeTruthy();
        expect(screen.getByRole('progressbar')).toBeTruthy();

        fireEvent.submit(screen.getByLabelText('Name').closest('form'));

        expect(formActions.post.mock.calls[0][0]).toBe('/install.user');
    });

    test.each([
        ['unlock', { codePath: 'x' }],
        ['environment', { groups }],
        ['database', {}],
        ['user', {}],
    ])('o passo %s não tem violações', async (step, props) => {
        const { container } = render(<InstallPage step={step} {...props} />);

        expect(await axe(container)).toHaveNoViolations();
    });
});
