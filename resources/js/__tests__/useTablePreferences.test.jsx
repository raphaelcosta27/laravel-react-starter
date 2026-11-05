import useTablePreferences from '@/Components/DataTable/useTablePreferences';
import { act, renderHook } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import { setPageProps } from './helpers/inertia';

const valid = { columns: ['name', 'email'], filters: ['name'] };

const hook = (tableKey = 'users') =>
    renderHook(() => useTablePreferences(tableKey, valid)).result;

describe('useTablePreferences', () => {
    beforeEach(() => {
        localStorage.clear();
        setPageProps({ auth: { user: { id: 7 } } });
    });

    afterEach(() => vi.restoreAllMocks());

    test('grava e lê pela chave do usuário e da listagem', () => {
        const result = hook();

        act(() => result.current.save({ perPage: 10, sort: 'name' }));

        expect(
            JSON.parse(localStorage.getItem('datatable:7:users:v1')),
        ).toMatchObject({ perPage: 10, sort: 'name' });
        expect(hook().current.preferences).toMatchObject({
            perPage: 10,
            sort: 'name',
        });
    });

    test('separa usuários no mesmo navegador', () => {
        const result = hook();

        act(() => result.current.save({ perPage: 10 }));

        setPageProps({ auth: { user: { id: 8 } } });

        expect(hook().current.preferences).toEqual({});
    });

    test('separa listagens', () => {
        const result = hook('users');

        act(() => result.current.save({ perPage: 10 }));

        expect(hook('profiles').current.preferences).toEqual({});
    });

    test('descarta coluna e filtro que não existem mais', () => {
        localStorage.setItem(
            'datatable:7:users:v1',
            JSON.stringify({
                columnOrder: ['email', 'antiga', 'name'],
                columnVisibility: { name: false, antiga: false },
                columnSizing: { name: 200, antiga: 90 },
                filter: { name: 'ana', antigo: 'x' },
            }),
        );

        expect(hook().current.preferences).toEqual({
            columnOrder: ['email', 'name'],
            columnVisibility: { name: false },
            columnSizing: { name: 200 },
            filter: { name: 'ana' },
        });
    });

    test('tolera JSON inválido', () => {
        localStorage.setItem('datatable:7:users:v1', '{quebrado');

        expect(hook().current.preferences).toEqual({});
    });

    test('tolera localStorage bloqueado', () => {
        vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
            throw new Error('bloqueado');
        });
        vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
            throw new Error('bloqueado');
        });

        const result = hook();

        expect(result.current.preferences).toEqual({});
        expect(() =>
            act(() => result.current.save({ perPage: 10 })),
        ).not.toThrow();
    });

    test('não guarda seleção nem linhas expandidas', () => {
        const result = hook();

        act(() =>
            result.current.save({
                perPage: 10,
                rowSelection: { 1: true },
                expanded: { 1: true },
            }),
        );

        expect(
            Object.keys(JSON.parse(localStorage.getItem('datatable:7:users:v1'))),
        ).toEqual(['perPage']);
    });

    test('limpar remove a chave', () => {
        const result = hook();

        act(() => result.current.save({ perPage: 10 }));
        act(() => result.current.clear());

        expect(localStorage.getItem('datatable:7:users:v1')).toBeNull();
    });
});
