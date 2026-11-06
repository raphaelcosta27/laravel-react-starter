import { readdirSync, readFileSync } from 'node:fs';
import { join, relative } from 'node:path';
import { describe, expect, test } from 'vitest';

const ROOT = join(process.cwd(), 'resources/js');

const sources = (directory) =>
    readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
        const path = join(directory, entry.name);

        if (entry.isDirectory()) {
            return entry.name === '__tests__' ? [] : sources(path);
        }

        return entry.name.endsWith('.jsx') ? [path] : [];
    });

const BUTTON = /<Button\b(?:(?!<\/Button>)[\s\S])*<\/Button>/g;
const FIELD = /<TextField\b(?:(?!\/>)[\s\S])*\/>/g;
const SENSITIVE =
    /type="(email|password|tel)"|fieldProps\(\w+, '(email|password|current_password|password_confirmation)'/;
const FIXED_COLOR = /(['"])grey\.\d+\1|['"]#[0-9a-f]{3,8}['"]|common\.white/i;

const buttonsWithoutIcon = (source) =>
    (source.match(BUTTON) ?? []).filter(
        (button) => !/startIcon|endIcon/.test(button),
    );

const fieldsWithoutIcon = (source) =>
    (source.match(FIELD) ?? []).filter(
        (field) => SENSITIVE.test(field) && !/kind:|adornment/.test(field),
    );

const hasFixedColor = (source) => FIXED_COLOR.test(source);

const files = sources(ROOT).map((path) => ({
    name: relative(ROOT, path).replaceAll('\\', '/'),
    source: readFileSync(path, 'utf8'),
}));

const offenders = (check) =>
    files.filter(({ source }) => check(source)).map(({ name }) => name);

describe('regras de desenho', () => {
    test('todo botão tem ícone', () => {
        expect(
            offenders((source) => buttonsWithoutIcon(source).length > 0),
        ).toEqual([]);
    });

    test('nenhuma tela usa cor fixa', () => {
        expect(offenders(hasFixedColor)).toEqual([]);
    });

    test('campos de e-mail, senha e telefone têm ícone', () => {
        expect(
            offenders((source) => fieldsWithoutIcon(source).length > 0),
        ).toEqual([]);
    });
});

describe('as regras apontam a falha', () => {
    test('botão sem ícone', () => {
        expect(buttonsWithoutIcon('<Button onClick={go}>Salvar</Button>')).toHaveLength(1);
        expect(
            buttonsWithoutIcon('<Button startIcon={<Add />}>Novo</Button>'),
        ).toHaveLength(0);
    });

    test('cor fixa', () => {
        expect(hasFixedColor("sx={{ bgcolor: 'grey.100' }}")).toBe(true);
        expect(hasFixedColor("sx={{ color: '#ff0000' }}")).toBe(true);
        expect(hasFixedColor("sx={{ bgcolor: 'common.white' }}")).toBe(true);
        expect(hasFixedColor("sx={{ bgcolor: 'background.paper' }}")).toBe(false);
    });

    test('campo sem ícone', () => {
        expect(fieldsWithoutIcon('<TextField type="email" />')).toHaveLength(1);
        expect(
            fieldsWithoutIcon("<TextField {...fieldProps(form, 'password')} />"),
        ).toHaveLength(1);
        expect(
            fieldsWithoutIcon(
                "<TextField {...fieldProps(form, 'email', { kind: 'email' })} />",
            ),
        ).toHaveLength(0);
        expect(fieldsWithoutIcon('<TextField label="Nome" />')).toHaveLength(0);
    });
});
