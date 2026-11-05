import { useTranslations } from '@/lib/i18n';
import { render, screen } from '@testing-library/react';
import { expect, test } from 'vitest';
import { setPageProps } from './helpers/inertia';

function Text({ value, params }) {
    const __ = useTranslations();

    return <p>{__(value, params)}</p>;
}

test('retorna a tradução', () => {
    setPageProps({ translations: { Login: 'Entrar' } });

    render(<Text value="Login" />);

    expect(screen.getByText('Entrar')).toBeTruthy();
});

test('retorna a própria chave quando não há tradução', () => {
    render(<Text value="Sem tradução" />);

    expect(screen.getByText('Sem tradução')).toBeTruthy();
});

test('substitui parâmetros', () => {
    setPageProps({ translations: { Hello: 'Olá, :name' } });

    render(<Text value="Hello" params={{ name: 'Ana' }} />);

    expect(screen.getByText('Olá, Ana')).toBeTruthy();
});
