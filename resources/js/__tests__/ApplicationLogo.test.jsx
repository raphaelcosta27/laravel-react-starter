import ApplicationLogo from '@/Components/ApplicationLogo';
import { render, screen } from '@testing-library/react';
import { axe } from 'jest-axe';
import { expect, test } from 'vitest';
import { setPageProps } from './helpers/inertia';

test('exibe o logo configurado com o nome da aplicação', async () => {
    setPageProps({
        appName: 'Minha Empresa',
        branding: { logo: '/marca.png' },
    });

    const { container } = render(<ApplicationLogo className="h-10" />);
    const image = screen.getByRole('img', { name: 'Minha Empresa' });

    expect(image.getAttribute('src')).toBe('/marca.png');
    expect(container.querySelector('svg')).toBeNull();
    expect(await axe(container)).toHaveNoViolations();
});
