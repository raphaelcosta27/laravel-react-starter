import RequestAccess from '@/Pages/Auth/RequestAccess';
import { render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { expect, test } from 'vitest';

test('tem campo rotulado, sem violações', async () => {
    const { container } = render(<RequestAccess />);

    expect(screen.getByLabelText('Email')).toBeTruthy();
    expect(await axe(container)).toHaveNoViolations();
});

test('exibe a resposta do pedido', async () => {
    const { container } = render(<RequestAccess status="Pedido recebido" />);

    expect(screen.getByRole('status').textContent).toBe('Pedido recebido');
    expect(await axe(container)).toHaveNoViolations();
});
