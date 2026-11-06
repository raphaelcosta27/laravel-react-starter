import { createAppTheme } from '@/theme';
import EditIcon from '@mui/icons-material/Edit';
import { getContrastRatio } from '@mui/material/styles';
import { describe, expect, test } from 'vitest';

describe('createAppTheme', () => {
    test('os campos usam a variante standard', () => {
        expect(
            createAppTheme().components.MuiTextField.defaultProps.variant,
        ).toBe('standard');
    });

    test('usa as cores configuradas', () => {
        const theme = createAppTheme({
            primaryColor: '#00695c',
            secondaryColor: '#6a1b9a',
        });

        expect(theme.palette.primary.main).toBe('#00695c');
        expect(theme.palette.secondary.main).toBe('#6a1b9a');
    });

    test.each([[undefined], [null], [''], ['azul'], ['#12']])(
        'cor inválida (%s) usa o padrão sem lançar erro',
        (color) => {
            const fallback = createAppTheme();
            const theme = createAppTheme({
                primaryColor: color,
                secondaryColor: color,
            });

            expect(theme.palette.primary.main).toBe(
                fallback.palette.primary.main,
            );
            expect(theme.palette.secondary.main).toBe(
                fallback.palette.secondary.main,
            );
        },
    );

    test('as cores padrão têm contraste AA sobre branco', () => {
        const { palette } = createAppTheme();

        expect(
            getContrastRatio(palette.primary.main, '#fff'),
        ).toBeGreaterThanOrEqual(4.5);
        expect(
            getContrastRatio(palette.secondary.main, '#fff'),
        ).toBeGreaterThanOrEqual(4.5);
    });

    test('o texto sobre a cor principal mantém contraste AA', () => {
        const { palette } = createAppTheme({ primaryColor: '#ffeb3b' });

        expect(
            getContrastRatio(palette.primary.main, palette.primary.contrastText),
        ).toBeGreaterThanOrEqual(4.5);
    });

    test('ícones importados por caminho funcionam nos testes', () => {
        expect(EditIcon).toBeTruthy();
    });
});
