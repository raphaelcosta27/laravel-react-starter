import PermissionFormDialog from '@/Pages/Admin/Permissions/Partials/PermissionFormDialog';
import { render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { beforeEach, describe, expect, test } from 'vitest';
import translations from '../../../lang/pt_BR.json';
import { setPageProps } from './helpers/inertia';

const creating = { mode: 'create', record: null };

const editing = {
    mode: 'edit',
    record: {
        id: 3,
        title: 'Gerar',
        code: 'gerar_relatorio',
        description: 'Permite gerar',
    },
};

describe('formulário de permissão', () => {
    beforeEach(() => setPageProps({ translations }));

    test('criação oferece título, código e descrição', () => {
        render(<PermissionFormDialog form={creating} />);

        expect(screen.getByRole('dialog', { name: 'Nova permissão' })).toBeTruthy();
        expect(screen.getByLabelText(/^Título/)).toBeTruthy();
        expect(screen.getByLabelText(/^Código/).readOnly).toBe(false);
        expect(screen.getByLabelText(/^Descrição/)).toBeTruthy();
    });

    test('o código é somente leitura na edição', () => {
        render(<PermissionFormDialog form={editing} />);

        expect(screen.getByRole('dialog', { name: 'Editar permissão' })).toBeTruthy();
        expect(screen.getByLabelText(/^Código/).readOnly).toBe(true);
        expect(screen.getByLabelText(/^Código/).value).toBe('gerar_relatorio');
    });

    test('exibe erros por campo no lugar da ajuda', () => {
        setPageProps({ errors: { code: 'Código já usado' } });

        render(<PermissionFormDialog form={creating} />);

        expect(screen.getByText('Código já usado')).toBeTruthy();
    });

    test('não tem violações de acessibilidade', async () => {
        const { baseElement } = render(<PermissionFormDialog form={editing} />);

        expect(await axe(baseElement)).toHaveNoViolations();
    });
});
