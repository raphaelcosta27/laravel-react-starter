import { screen, within } from '@testing-library/react';
import { expect } from 'vitest';

/** Toda ação de linha DEVE ter ícone e descrição. */
export function expectDescribedActions() {
    const groups = screen.getAllByRole('group', { name: 'Ações' });
    const actions = groups.flatMap((group) => [
        ...within(group).queryAllByRole('button'),
        ...within(group).queryAllByRole('link'),
    ]);

    expect(actions.length).toBeGreaterThan(0);

    actions.forEach((action) => {
        expect(action.querySelector('svg')).toBeTruthy();
        expect(
            document.getElementById(action.getAttribute('aria-describedby'))
                ?.textContent,
        ).toBeTruthy();
    });
}
