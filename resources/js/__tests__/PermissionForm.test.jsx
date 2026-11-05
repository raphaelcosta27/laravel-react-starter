import Form from '@/Pages/Admin/Permissions/Form';
import { render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';

const auth = {
    user: { id: 1, name: 'Ana', email: 'ana@example.com' },
    isAdministrator: true,
    permissions: [],
};

const permission = {
    id: 3,
    title: 'Gerar',
    code: 'gerar_relatorio',
    description: 'Permite gerar',
};

describe('formulário de permissão', () => {
    beforeEach(() => setPageProps({ translations, auth }));

    test('criação oferece título, código e descrição', () => {
        render(<Form permission={null} />);

        expect(screen.getByRole('heading', { level: 1, name: 'Nova permissão' })).toBeTruthy();
        expect(screen.getByLabelText('Título')).toBeTruthy();
        expect(screen.getByLabelText('Código').readOnly).toBe(false);
        expect(screen.getByLabelText('Descrição')).toBeTruthy();
    });

    test('o código é somente leitura na edição', () => {
        render(<Form permission={permission} />);

        expect(screen.getByRole('heading', { level: 1, name: 'Editar permissão' })).toBeTruthy();
        expect(screen.getByLabelText('Código').readOnly).toBe(true);
        expect(screen.getByLabelText('Código').value).toBe('gerar_relatorio');
    });

    test('exibe erros por campo', () => {
        setPageProps({ errors: { code: 'Código já usado' } });

        render(<Form permission={null} />);

        expect(screen.getByText('Código já usado')).toBeTruthy();
    });

    test('não tem violações de acessibilidade', async () => {
        const { container } = render(<Form permission={permission} />);

        expect(await axe(container)).toHaveNoViolations();
    });
});
