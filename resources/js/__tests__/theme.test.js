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

    test.each([
        ['#3949ab'],
        ['#ffeb3b'],
        ['#0d1b4c'],
        ['#ffffff'],
        ['#000000'],
    ])('%s fica legível nos dois esquemas', (brand) => {
        const { colorSchemes } = createAppTheme({
            primaryColor: brand,
            secondaryColor: brand,
        });

        ['light', 'dark'].forEach((name) => {
            const { palette } = colorSchemes[name];

            [palette.background.default, palette.background.paper].forEach(
                (surface) => {
                    ['primary', 'secondary'].forEach((tone) => {
                        expect(
                            getContrastRatio(palette[tone].main, surface),
                        ).toBeGreaterThanOrEqual(4.5);
                    });
                    expect(
                        getContrastRatio(palette.text.primary, surface),
                    ).toBeGreaterThanOrEqual(4.5);
                    expect(
                        getContrastRatio(palette.text.secondary, surface),
                    ).toBeGreaterThanOrEqual(4.5);
                },
            );

            expect(
                getContrastRatio(palette.primary.main, palette.primary.contrastText),
            ).toBeGreaterThanOrEqual(4.5);
        });
    });

    test('o esquema fica em um atributo do documento', () => {
        const theme = createAppTheme();

        expect(theme.colorSchemeSelector).toBe('data-color-scheme');
        expect(theme.shape.borderRadius).toBe(12);
        expect(theme.colorSchemes.dark.palette.mode).toBe('dark');
    });

    test('ícones importados por caminho funcionam nos testes', () => {
        expect(EditIcon).toBeTruthy();
    });
});
